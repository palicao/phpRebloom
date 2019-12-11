<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use RedisException;

final class CuckooFilter extends BaseFilter
{
    /**
     * @param $key
     * @param int $capacity
     * @param int|null $bucketSize
     * @param int|null $maxIterations
     * @param int|null $expansion
     * @return bool
     * @throws RedisException
     */
    public function reserve(string $key, int $capacity, ?int $bucketSize = null, ?int $maxIterations = null, ?int $expansion = null): bool
    {
        $params = ['CF.RESERVE', $key, $capacity];
        if ($bucketSize !== null) {
            $params[] = 'BUCKETSIZE';
            $params[] = $bucketSize;
        }
        if ($maxIterations !== null) {
            $params[] = 'MAXITERATIONS';
            $params[] = $maxIterations;
        }
        if ($expansion !== null) {
            $params[] = 'EXPANSION';
            $params[] = $expansion;
        }
        return (bool)$this->client->executeCommand($params);
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $allowDuplicateValues
     * @param int|null $capacity
     * @return bool
     * @throws RedisException
     */
    public function insert(string $key, string $value, bool $allowDuplicateValues = true, ?int $capacity = null): bool
    {
        $result = $this->doInsert($key, [$value], $allowDuplicateValues, true, $capacity);
        return array_pop($result);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @param bool $allowDuplicateValues
     * @param int|null $capacity
     * @return bool[]
     * @throws RedisException
     */
    public function insertMany(string $key, array $values, bool $allowDuplicateValues = true, ?int $capacity = null): array
    {
        return $this->doInsert($key, $values, $allowDuplicateValues, true, $capacity);
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $allowDuplicateValues
     * @param int|null $capacity
     * @return bool
     * @throws RedisException
     */
    public function insertIfKeyExists(string $key, string $value, bool $allowDuplicateValues = true, ?int $capacity = null): bool
    {
        $result = $this->doInsert($key, [$value], $allowDuplicateValues, false, $capacity);
        return array_pop($result);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @param bool $allowDuplicateValues
     * @param int|null $capacity
     * @return bool[]
     * @throws RedisException
     */
    public function insertManyIfKeyExists(string $key, array $values, bool $allowDuplicateValues = true, ?int $capacity = null): array
    {
        return $this->doInsert($key, $values, $allowDuplicateValues, false, $capacity);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @param bool $allowDuplicateValues
     * @param bool $createKey
     * @param int|null $capacity
     * @return bool[]
     * @throws RedisException
     */
    private function doInsert(string $key, array $values, bool $allowDuplicateValues, bool $createKey, ?int $capacity): array
    {
        $affix = $allowDuplicateValues ? '' : 'NX';

        $count = count($values);
        if ($count === 0) {
            return [];
        }
        if ($capacity === null && $count === 1 && $createKey) {
            return self::arrayToBool([$this->client->executeCommand(['CF.ADD' . $affix, $key, array_pop($values)])]);
        }

        $params = ['CF.INSERT' . $affix, $key];
        if ($capacity !== null) {
            $params[] = 'CAPACITY';
            $params[] = $capacity;
        }
        if (!$createKey) {
            $params[] = 'NOCREATE';
        }
        $params[] = 'ITEMS';
        $result = $this->client->executeCommand(array_merge($params, $values));
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
        return (bool)$this->client->executeCommand(['CF.EXISTS', $key, $value]);
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     * @throws RedisException
     */
    public function delete(string $key, string $value): bool
    {
        return (bool)$this->client->executeCommand(['CF.DEL', $key, $value]);
    }

    /**
     * @param string $key
     * @param string $value
     * @return int
     * @throws RedisException
     */
    public function count(string $key, string $value): int
    {
        return (int)$this->client->executeCommand(['CF.COUNT', $key, $value]);
    }

    /**
     * @param string $key
     * @return CuckooFilterInfo
     * @throws RedisException
     * @throws KeyNotFoundException
     */
    public function info(string $key): CuckooFilterInfo
    {
        $info = $this->client->executeCommand(['CF.INFO', $key]);
        if (!is_array($info)) {
            throw new KeyNotFoundException(sprintf('Key %s does not exist', $key));
        }

        return new CuckooFilterInfo(
            $key,
            $info[1],
            $info[3],
            $info[5],
            $info[7],
            $info[9],
            $info[11],
            $info[13],
            $info[15]
        );
    }

    /**
     * @param string $key
     * @return array
     * @throws RedisException
     */
    public function scanDump(string $key): array
    {
        return $this->doScanDump($key, 'CF');
    }

    /**
     * @param string $key
     * @param array $chunks
     * @throws RedisException
     */
    public function loadChunks(string $key, array $chunks): void
    {
        $this->doLoadChunks($key, $chunks, 'CF');
    }
}
