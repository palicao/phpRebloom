<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use Palicao\PhpRebloom\Exception\ErrorRateOutOfBoundsException;
use RedisException;

final class BloomFilter extends BaseFilter
{
    /**
     * @param string $key
     * @param float $error
     * @param int $capacity
     * @return bool
     * @throws RedisException
     */
    public function reserve(string $key, float $error, int $capacity): bool
    {
        $this->assertErrorRateIsInRange($error);
        return (bool)$this->client->executeCommand(['BF.RESERVE', $key, $error, $capacity]);
    }

    /**
     * @param string $key
     * @param string $value
     * @param float|null $error
     * @param int|null $capacity
     * @return bool
     * @throws RedisException
     */
    public function insert(string $key, string $value, ?float $error = null, ?int $capacity = null): bool
    {
        $result = $this->insertMany($key, [$value], $error, $capacity);
        return array_pop($result);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @param float|null $error
     * @param int|null $capacity
     * @return bool[]
     * @throws RedisException
     */
    public function insertMany(string $key, array $values, ?float $error = null, ?int $capacity = null): array
    {
        $count = count($values);
        if ($count === 0) {
            return [];
        }
        if ($error === null && $capacity === null) {
            if ($count === 1) {
                $result = [$this->client->executeCommand(['BF.ADD', $key, array_pop($values)])];
            } else {
                $result = $this->client->executeCommand(array_merge(['BF.MADD', $key], $values));
            }
            return self::arrayToBool($result);
        }

        $params = ['BF.INSERT', $key];
        if ($capacity !== null) {
            $params[] = 'CAPACITY';
            $params[] = $capacity;
        }
        if ($error !== null) {
            $this->assertErrorRateIsInRange($error);
            $params[] = 'ERROR';
            $params[] = $error;
        }

        $result = $this->client->executeCommand(array_merge($params, ['ITEMS'], $values));
        return self::arrayToBool($result);
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     * @throws RedisException
     */
    public function insertIfKeyExists(string $key, string $value): bool
    {
        $result = $this->insertManyIfKeyExists($key, [$value]);
        return array_pop($result);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @return bool[]
     * @throws RedisException
     */
    public function insertManyIfKeyExists(string $key, array $values): array
    {
        if (count($values) === 0) {
            return [];
        }
        $result = $this->client->executeCommand(array_merge(['BF.INSERT', $key, 'NOCREATE', 'ITEMS'], $values));
        if ($result === false) {
            throw new KeyNotFoundException(sprintf('Key %s does not exist', $key));
        }
        return self::arrayToBool($result);
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     * @throws RedisException
     */
    public function exists(string $key, string $value): bool
    {
        return (bool)$this->client->executeCommand(['BF.EXISTS', $key, $value]);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @return bool[]
     * @throws RedisException
     */
    public function manyExist(string $key, array $values): array
    {
        if (count($values) === 0) {
            return [];
        }
        return self::arrayToBool($this->client->executeCommand(array_merge(['BF.MEXISTS', $key], $values)));
    }

    /**
     * @param string $key
     * @return array
     * @throws RedisException
     */
    public function scanDump(string $key): array
    {
        return $this->doScanDump($key, 'BF');
    }

    /**
     * @param string $key
     * @param array $chunks
     * @throws RedisException
     */
    public function loadChunks(string $key, array $chunks): void
    {
        $this->doLoadChunks($key, $chunks, 'BF');
    }

    /**
     * @param float $error
     * @throws ErrorRateOutOfBoundsException
     */
    private function assertErrorRateIsInRange(float $error): void
    {
        if ($error < 0 || $error > 1) {
            throw new ErrorRateOutOfBoundsException(sprintf('Error rate must be >= 0 and <= 1'));
        }
    }
}
