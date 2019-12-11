<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\LoadChunksException;
use RedisException;

abstract class BaseFilter
{
    /** @var RedisClient */
    protected $client;

    public function __construct(RedisClient $redisClient)
    {
        $this->client = $redisClient;
    }

    /**
     * @param string $key
     * @param string $type
     * @return array
     * @throws RedisException
     */
    protected function doScanDump(string $key, string $type): array
    {
        $chunks = [];
        $iter = 0;
        while (true) {
            [$iter, $data] = $this->client->executeCommand([$type . '.SCANDUMP', $key, $iter]);
            if ($iter === 0 || $iter === null) {
                break;
            }
            $chunks[$iter] = $data;
        }
        return $chunks;
    }

    /**
     * @param string $key
     * @param array $chunks
     * @param string $type
     * @throws RedisException
     * @throws LoadChunksException
     */
    protected function doLoadChunks(string $key, array $chunks, string $type): void
    {
        foreach ($chunks as $iter => $chunk) {
            $result = $this->client->executeCommand([$type . '.LOADCHUNK', $key, $iter, $chunk]);
            if (!$result) {
                throw new LoadChunksException(sprintf('Impossible to load chunk %d into key %s', $iter, $key));
            }
        }
    }

    /**
     * @param string $key
     * @return array
     * @throws RedisException
     */
    abstract public function scanDump(string $key): array;

    /**
     * @param string $key
     * @param array $chunks
     * @throws RedisException
     * @throws LoadChunksException
     */
    abstract public function loadChunks(string $key, array $chunks): void;

    /**
     * @param string $sourceKey
     * @param string $destKey
     * @throws RedisException
     * @throws LoadChunksException
     */
    public function copy(string $sourceKey, string $destKey): void
    {
        $this->loadChunks($destKey, $this->scanDump($sourceKey));
    }

    /**
     * @param array $result
     * @return bool[]
     */
    protected static function arrayToBool(array $result): array
    {
        return array_map('boolval', $result);
    }
}
