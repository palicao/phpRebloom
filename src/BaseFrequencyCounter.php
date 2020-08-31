<?php

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use RedisException;

abstract class BaseFrequencyCounter
{
    /** @var RedisClient */
    protected $client;

    public function __construct(RedisClient $redisClient)
    {
        $this->client = $redisClient;
    }

    /**
     * @param RedisException $exception
     * @param string $key
     * @throws RedisException
     * @throws KeyNotFoundException
     */
    protected function parseException(RedisException $exception, ?string $key = null): void
    {
        if (stripos($exception->getMessage(), 'key does not exist') !== false) {
            $msg = $key !== null ? sprintf('Key %s does not exist', $key) : 'Key does not exist';
            throw new KeyNotFoundException($msg);
        }
        throw $exception;
    }

    /**
     * @param string|bool $result
     * @return bool
     */
    protected function parseResult($result): bool
    {
        return $result === 'OK' ? true : (bool) $result;
    }
}
