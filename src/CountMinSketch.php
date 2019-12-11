<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use Palicao\PhpRebloom\Exception\RedisClientException;
use RedisException;

final class CountMinSketch extends BaseFrequencyCounter
{
    /**
     * @param string $key
     * @param int $width
     * @param int $depth
     * @return bool
     * @throws RedisException
     */
    public function initByDimensions(string $key, int $width, int $depth): bool
    {
        return $this->parseResult(
            $this->client->executeCommand(['CMS.INITBYDIM', $key, $width, $depth])
        );
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
        return $this->parseResult(
            $this->client->executeCommand(['CMS.INITBYPROB', $key, $error, $probability])
        );
    }

    /**
     * @param string $key
     * @param Pair ...$pairs
     * @return bool
     * @throws RedisException
     */
    public function incrementBy(string $key, Pair ...$pairs): bool
    {
        $params = ['CMS.INCRBY', $key];
        foreach ($pairs as $pair) {
            $params[] = $pair->getItem();
            $params[] = $pair->getValue();
        }
        try {
            return $this->parseResult($this->client->executeCommand($params));
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
    public function merge(string $destinationKey, array $sourceKeysWeightMap) : bool
    {
        try {
            $count = count($sourceKeysWeightMap);
            $sourceKeys = array_keys($sourceKeysWeightMap);
            $weights = array_values($sourceKeysWeightMap);
            $params = array_merge(['CMS.MERGE', $destinationKey, $count], $sourceKeys, ['WEIGHTS'], $weights);
            return $this->parseResult($this->client->executeCommand($params));
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
        }
        return new CountMinSketchInfo($key, $result[1], $result[3], $result[5]);
    }
}