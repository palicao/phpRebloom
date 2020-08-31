<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRebloom\Tests\Integration;

use Palicao\PhpRebloom\BloomFilter;
use Palicao\PhpRebloom\Exception\RedisAuthenticationException;
use Palicao\PhpRebloom\RedisClient;
use Palicao\PhpRebloom\RedisConnectionParams;
use Redis;

class AuthRedis5Test extends IntegrationTestCase
{
    public function setUp(): void
    {
        if (self::getRedisMajorVersion() > 5) {
            self::markTestSkipped('This test is supposed to run on Redis version 5 or lower');
        }
        parent::setUp();
    }

    public function testAuthWithPasswordSuccess(): void
    {
        $this->redisClient->executeCommand(['CONFIG', 'SET', 'requirepass', 'pass123']);
        $this->redis->close();
        $connectionParams = new RedisConnectionParams(self::getHost(), self::getPort(), null, 'pass123');
        $authorizedRedisClient = new RedisClient(new Redis(), $connectionParams);
        $bloomFilter = new BloomFilter($authorizedRedisClient);
        $result = $bloomFilter->reserve('reserveTest', .0001, 100);
        self::assertTrue($result);
        $authorizedRedisClient->executeCommand(['CONFIG', 'SET', 'requirepass', '']);
    }

    public function testAuthWithPasswordFailure(): void
    {
        $this->expectException(RedisAuthenticationException::class);
        $this->redisClient->executeCommand(['CONFIG', 'SET', 'requirepass', 'pass123']);
        $this->redis->close();
        $connectionParams = new RedisConnectionParams(self::getHost(), self::getPort(), null, 'foobar');
        $nonAuthorizedRedisClient = new RedisClient(new Redis(), $connectionParams);
        $bloomFilter = new BloomFilter($nonAuthorizedRedisClient);
        try {
            $bloomFilter->reserve('reserveTest', .0001, 100);
        } catch (RedisAuthenticationException $exception) {
            throw $exception;
        } finally {
            $connectionParams = new RedisConnectionParams(self::getHost(), self::getPort(), null, 'pass123');
            $authorizedRedisClient = new RedisClient(new Redis(), $connectionParams);
            $authorizedRedisClient->executeCommand(['CONFIG', 'SET', 'requirepass', '']);
        }
    }
}
