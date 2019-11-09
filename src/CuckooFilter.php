<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

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
    public function reserve($key, int $capacity, ?int $bucketSize, ?int $maxIterations, ?int $expansion)
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
     * @param string[] $values
     * @param bool $allowDuplicates
     * @param int|null $capacity
     * @return bool[]
     * @throws RedisException
     */
    public function insertMany(string $key, array $values, bool $allowDuplicates = false, ?int $capacity = null): array
    {
        return $this->doInsert($key, $values, $allowDuplicates, true, $capacity);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @param bool $allowDuplicates
     * @param int|null $capacity
     * @return bool[]
     * @throws RedisException
     */
    public function insertIfKeyExists(string $key, array $values, bool $allowDuplicates = false, ?int $capacity = null): array
    {
        return $this->doInsert($key, $values, $allowDuplicates, false, $capacity);
    }

    /**
     * @param string $key
     * @param string[] $values
     * @param bool $allowDuplicates
     * @param bool $createKey
     * @param int|null $capacity
     * @return bool[]
     * @throws RedisException
     */
    private function doInsert(string $key, array $values, bool $allowDuplicates, bool $createKey, ?int $capacity): array
    {
        $affix = $allowDuplicates ? '' : 'NX';

        $count = count($values);
        if ($count === 0) {
            return [];
        }
        if ($capacity === null && $count === 1) {
            return $this->toBool([$this->client->executeCommand(['CF.ADD' . $affix, $key, array_pop($values)])]);
        }

        $params = ['CF.INSERT' . $affix, $key, 'CAPACITY', $capacity];
        if (!$createKey) {
            $params[] = 'NOCREATE';
        }
        $params[] = 'ITEMS';
        return $this->toBool($this->client->executeCommand(array_merge($params, $values)));
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
