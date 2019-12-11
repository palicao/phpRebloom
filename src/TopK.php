<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use RedisException;

final class TopK extends BaseFrequencyCounter
{
    /**
     * @param string $key
     * @param int $topK
     * @param int $width
     * @param int $depth
     * @param float $decay
     * @return bool
     * @throws RedisException
     */
    public function reserve(string $key, int $topK, int $width, int $depth, float $decay): bool
    {
        return $this->parseResult(
            $this->client->executeCommand(['TOPK.RESERVE', $key, $topK, $width, $depth, $decay])
        );
    }

    /**
     * @param string $key
     * @param string ...$items
     * @return array Returns, for each added item, `false` if nothing changed, or the name of the item that
     * was expelled from the top-k by the added item.
     * @throws RedisException
     */
    public function add(string $key, string ... $items): array
    {
        try {
            return $this->client->executeCommand(array_merge(['TOPK.ADD', $key], $items));
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        return [];
    }

    /**
     * @param string $key
     * @param Pair ...$pairs
     * @return array Returns, for each Pair item, `false` if nothing changed, or the name of the item that
     * was expelled from the top-k by the modified item.
     * @throws RedisException
     */
    public function incrementBy(string $key, Pair ...$pairs): array
    {
        $params = ['TOPK.INCRBY', $key];
        foreach ($pairs as $pair) {
            $params[] = $pair->getItem();
            $params[] = $pair->getValue();
        }
        try {
            return $this->client->executeCommand($params);
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        return [];
    }

    /**
     * @param string $key
     * @param string ...$items
     * @return string[] A subset of $items containing the elements found in the top-k
     * @throws RedisException
     */
    public function query(string $key, string ... $items): array
    {
        $out = [];
        try {
            $result = $this->client->executeCommand(array_merge(['TOPK.QUERY', $key], $items));
            $resultCount = count($result);
            for ($i = 0; $i < $resultCount; $i++) {
                if (isset($items[$i]) && $result[$i] === 1) {
                    $out[] = $items[$i];
                }
            }

        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        return $out;
    }

    /**
     * @param string $key
     * @param string ...$items
     * @return Pair[] A subset of $items containing the elements found in the top-k with their approximate count
     * @throws RedisException
     */
    public function count(string $key, string ... $items): array
    {
        $out = [];
        try {
            $result = $this->client->executeCommand(array_merge(['TOPK.COUNT', $key], $items));
            $resultCount = count($result);
            for ($i = 0; $i < $resultCount; $i++) {
                if (isset($items[$i])) {
                    $out[] = new Pair($items[$i], (int)$result[$i]);
                }
            }
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        return $out;
    }

    /**
     * @param string $key
     * @return Pair[] The top-k items with their relative position
     * @throws RedisException
     */
    public function list(string $key): array
    {
        $out = [];
        try {
            $result = $this->client->executeCommand(['TOPK.LIST', $key]);
            foreach ($result as $position => $item) {
                if ($item !== false) {
                    $out[] = new Pair($item, (int)$position);
                }
            }
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        return $out;
    }

    /**
     * @param string $key
     * @return TopKInfo
     * @throws RedisException
     */
    public function info(string $key): TopKInfo
    {
        $result = [];
        try {
            $result = $this->client->executeCommand(['TOPK.INFO', $key]);
        } catch (RedisException $exception) {
            $this->parseException($exception, $key);
        }
        return new TopKInfo($key, (int)$result[1], (int)$result[3], (int)$result[5], (float)$result[7]);
    }
}