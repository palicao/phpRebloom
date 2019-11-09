<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRebloom\Tests\Integration;

use Palicao\PhpRebloom\BloomFilter;
use Palicao\PhpRebloom\Exception\ErrorRateOutOfBoundsException;
use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use Palicao\PhpRebloom\RedisClient;
use Palicao\PhpRebloom\RedisConnectionParams;
use PHPUnit\Framework\TestCase;
use Redis;

class BloomFilterIntegrationTest extends TestCase
{
    private $redis;
    private $sut;

    public function setUp(): void
    {
        $host = getenv('REDIS_HOST') ?: 'redis';
        $port = getenv('REDIS_PORT') ? (int)getenv('REDIS_PORT') : 6379;
        $this->redis = new Redis();
        $this->redis->connect($host, $port);

        $connectionParams = new RedisConnectionParams($host, $port);
        $redisClient = new RedisClient($this->redis, $connectionParams);
        $redisClient->executeCommand(['FLUSHDB']);
        $this->sut = new BloomFilter($redisClient);
    }

    public function testReserveCreatesKey(): void
    {
        $this->sut->reserve('reserveTest', .0001, 100);
        $result = (bool)$this->redis->exists('reserveTest');
        $this->assertTrue($result);
    }

    public function testInsert(): void
    {
        $this->sut->insert('insertTest', 'foo', .0001, 100);
        $this->assertTrue($this->sut->exists('insertTest', 'foo'));
        $this->assertFalse($this->sut->exists('insertTest', 'bar'));
    }

    public function testInsertWithoutErrorAndCapacity(): void
    {
        $this->sut->insert('insert2Test', 'foo');
        $this->assertTrue($this->sut->exists('insert2Test', 'foo'));
        $this->assertFalse($this->sut->exists('insert2Test', 'bar'));
    }

    public function testErrorOutOfBounds(): void
    {
        $this->expectException(ErrorRateOutOfBoundsException::class);
        $this->sut->insert('outOfBoundsTest', 'foo', 2.0, 10);
    }

    public function testInsertMany(): void
    {
        $this->sut->insertMany('insertManyTest', ['pear', 'orange', 'banana'], .0001, 100);
        $this->assertTrue($this->sut->exists('insertManyTest', 'orange'));
        $this->assertFalse($this->sut->exists('insertManyTest', 'pineapple'));
    }

    public function testInsertManyEmpty(): void
    {
        $this->assertEquals([], $this->sut->insertMany('insertManyTest', []));
    }

    public function testInsertManyWithoutErrorAndCapacity(): void
    {
        $this->sut->insertMany('insertMany2Test', ['pear', 'orange', 'banana']);
        $this->assertTrue($this->sut->exists('insertMany2Test', 'orange'));
        $this->assertFalse($this->sut->exists('insertMany2Test', 'pineapple'));
    }

    public function testInsertIfKeyExists(): void
    {
        $this->sut->reserve('insertIfKeyExistsTest', .0001, 100);
        $this->sut->insertIfKeyExists('insertIfKeyExistsTest', ['pear', 'orange', 'banana']);
        $this->assertTrue($this->sut->exists('insertIfKeyExistsTest', 'orange'));
        $this->assertFalse($this->sut->exists('insertIfKeyExistsTest', 'pineapple'));
    }

    public function testInsertIfKeyExistsThrowsExceptionOnMissingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->insertIfKeyExists('missingKey', ['foo', 'bar', 'baz']);
    }

    public function testManyExists(): void
    {
        $this->sut->insertMany('manyExistsTest', ['pear', 'orange', 'banana']);
        $this->assertEquals([true, true], $this->sut->manyExist('manyExistsTest', ['orange', 'banana']));
        $this->assertEquals([false, false], $this->sut->manyExist('manyExistsTest', ['pineapple', 'strawberry']));
        $this->assertEquals([false, true], $this->sut->manyExist('manyExistsTest', ['watermelon', 'orange']));
    }

    public function testCopy(): void
    {
        $this->sut->insertMany('copyFrom', ['pear', 'orange', 'banana']);
        $this->sut->copy('copyFrom', 'copyTo');
        $expected = [true, true, true, false, false, false];
        $returned = $this->sut->manyExist(
            'copyTo',
            ['pear', 'orange', 'banana', 'pineapple', 'strawberry', 'watermelon']
        );
        $this->assertEquals($expected, $returned);
    }
}
