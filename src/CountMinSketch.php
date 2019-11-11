<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use Palicao\PhpRebloom\Exception\RedisClientException;
use RedisException;

final class CountMinSketch
{
    /** @var RedisClient */
    protected $client;

    public function __construct(RedisClient $redisClient)
    {
        $this->client = $redisClient;
    }

    /**
     * @param string $key
     * @param int $width
     * @param int $depth
     * @return bool
     * @throws RedisException
     */
    public function initByDimensions(string $key, int $width, int $depth): bool
    {
        return $this->client->executeCommand(['CMS.INITBYDIM', $key, $width, $depth]);
    }

    /**
     * @param string $key
     * @param float $error
     * @param float $probability
     * @return bool
     * @throws RedisException
     */
    public function initByProbability(string $key, float $error, float $probability): bool
    {
        return $this->client->executeCommand(['CMS.INITBYPROB', $key, $error, $probability]);
    }

    /**
     * @param string $key
     * @param Pair ...$pairs
     * @return bool
     * @throws RedisException
     * @throws RedisClientException
     */
    public function incrementBy(string $key, Pair ...$pairs): bool
    {
        $params = ['CMS.INCRBY', $key];
        foreach ($pairs as $pair) {
            $params[] = $pair->getItem();
            $params[] = $pair->getValue();
        }
        try {
            return $this->client->executeCommand($params);
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        return false;
    }

    /**
     * @param string $key
     * @param string ...$items
     * @return Pair[]
     * @throws RedisException
     */
    public function query(string $key, string ... $items): array
    {
        $results = [];
        $out = [];
        try {
            $results = $this->client->executeCommand(array_merge(['CMS.QUERY', $key], $items));
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        $resultCount = count($results);
        for ($i = 0; $i < $resultCount; $i++) {
            $out[] = new Pair($items[$i], $results[$i]);
        }
        return $out;
    }

    /**
     * @param string $destinationKey
     * @param array $sourceKeysWeightMap
     * @return bool
     * @throws RedisException
     * @throws RedisClientException
     * @throws KeyNotFoundException
     */
    public function merge(string $destinationKey, array $sourceKeysWeightMap): bool
    {
        try {
            $count = count($sourceKeysWeightMap);
            $sourceKeys = array_keys($sourceKeysWeightMap);
            $weights = array_values($sourceKeysWeightMap);
            $params = array_merge(['CMS.MERGE', $destinationKey, $count], $sourceKeys, ['WEIGHTS'], $weights);
            return $this->client->executeCommand($params);
        } catch (RedisException $exception) {
            $this->parseException($exception);
        }
        return false;
    }

    /**
     * @param string $key
     * @return CountMinSketchInfo
     * @throws RedisException
     * @throws RedisClientException
     * @throws KeyNotFoundException
     */
    public function info(string $key): CountMinSketchInfo
    {
        $result = [];
        try {
            $result = $this->client->executeCommand(['CMS.INFO', $key]);
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        } finally {
            return new CountMinSketchInfo($key, $result[1], $result[3], $result[5]);
        }
    }

    /**
     * @param RedisException $exception
     * @param string $key
     * @throws RedisException
     * @throws KeyNotFoundException
     */
    private function parseException(RedisException $exception, ?string $key = null): void
    {
        if (stripos($exception->getMessage(), 'key does not exist') !== false) {
            $msg = $key !== null ? sprintf('Key %s does not exist', $key) : 'Key does not exist';
            throw new KeyNotFoundException($msg);
        }
        throw $exception;
    }

}