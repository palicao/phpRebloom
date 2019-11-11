<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRebloom\Tests\Integration;

use Palicao\PhpRebloom\RedisClient;
use Palicao\PhpRebloom\RedisConnectionParams;
use PHPUnit\Framework\TestCase;
use Redis;

abstract class IntegrationTestCase extends TestCase
{
    /** @var Redis */
    protected $redis;

    /** @var RedisClient */
    protected $redisClient;

    public function setUp(): void
    {
        $host = getenv('REDIS_HOST') ?: 'redis';
        $port = getenv('REDIS_PORT') ? (int)getenv('REDIS_PORT') : 6379;
        $this->redis = new Redis();
        $this->redis->connect($host, $port);

        $connectionParams = new RedisConnectionParams($host, $port);
        $this->redisClient = new RedisClient($this->redis, $connectionParams);
        $this->redisClient->executeCommand(['FLUSHDB']);
    }
}
