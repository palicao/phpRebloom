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
        $host = self::getHost();
        $port = self::getPort();
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
        $connectionParams = new RedisConnectionParams($host, $port);
        $this->redisClient = new RedisClient($this->redis, $connectionParams);
        $this->redisClient->executeCommand(['FLUSHDB']);
    }

    protected static function getHost(): string
    {
        return getenv('REDIS_HOST') ?: 'redis';
    }

    protected static function getPort(): int
    {
        return getenv('REDIS_PORT') ? (int)getenv('REDIS_PORT') : 6379;
    }

    protected static function getRedisMajorVersion(): int
    {
        $redis = new Redis();
        $redis->connect(self::getHost(), self::getPort());
        /** @var array $info */
        $info = $redis->info('SERVER');
        [$version] = explode('.', $info['redis_version']);
        return (int) $version;
    }
}
