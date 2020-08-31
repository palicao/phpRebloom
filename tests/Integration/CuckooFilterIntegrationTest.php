<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRebloom\Tests\Integration;

use Palicao\PhpRebloom\CuckooFilter;
use Palicao\PhpRebloom\Exception\KeyNotFoundException;

class CuckooFilterIntegrationTest extends IntegrationTestCase
{
    /**
     * @var CuckooFilter
     */
    private $sut;

    public function setUp(): void
    {
        parent::setUp();
        $this->sut = new CuckooFilter($this->redisClient);
    }

    public function testReserveAndInfo(): void
    {
        $key = 'reserve';
        self::assertTrue($this->sut->reserve($key, 200, 10, 20, 2));
        self::assertTrue((bool)$this->redis->exists($key));

        $info = $this->sut->info($key);
        self::assertEquals($key, $info->getKey(), 'Wrong key');
        self::assertEquals(376, $info->getSize(), 'Wrong size');
        self::assertEquals(10, $info->getBucketSize(), 'Wrong bucket size');
        self::assertEquals(0, $info->getDeletedItems(), 'Wrong deleted items');
        self::assertEquals(0, $info->getInsertedItems(), 'Wrong inserted items');
        self::assertEquals(2, $info->getExpansionRate(), 'Wrong expansion rate');
        self::assertEquals(20, $info->getMaxIterations(), 'Wrong max iterations');
        self::assertEquals(32, $info->getNumBuckets(), 'Wrong num buckets');
        self::assertEquals(1, $info->getNumFilters(), 'Wrong num filters');

    }

    public function testInsert(): void
    {
        $this->sut->insert('insertTest', 'horse');
        $result = $this->sut->exists('insertTest', 'horse');
        self::assertTrue($result);
    }

    public function testInsertMany(): void
    {
        $key = 'insertManyTest';
        $result = $this->sut->insertMany($key, ['horse', 'cow']);
        self::assertEquals([true, true], $result);
        self::assertTrue($this->sut->exists($key, 'horse'));
        self::assertFalse($this->sut->exists($key, 'monkey'));
    }

    public function testInsertIfKeyExists(): void
    {
        $key = 'testInsertIfKeyExists';
        $this->sut->reserve($key, 10);
        self::assertTrue($this->sut->insertIfKeyExists($key, 'foo'));
    }

    public function testInsertIfKeyExistsOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->insertIfKeyExists('insert2Test', 'foo');
    }

    public function testInsertManyIfKeyExistOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->insertManyIfKeyExists('insertMany2Test', ['foo', 'bar']);
    }

    public function testDelete(): void
    {
        $key = 'deleteTest';
        $this->sut->insertMany($key, ['horse', 'bat', 'rat']);
        $this->sut->delete($key, 'bat');
        self::assertTrue($this->sut->exists($key, 'horse'));
        self::assertFalse($this->sut->exists($key, 'bat'));
        self::assertTrue($this->sut->exists($key, 'rat'));
    }

    public function testCount(): void
    {
        $key = 'countTest';
        $this->sut->insertMany($key,  ['horse', 'bat', 'rat', 'horse', 'bat', 'horse']);
        self::assertEquals(3, $this->sut->count($key, 'horse'));
        self::assertEquals(2, $this->sut->count($key, 'bat'));
        self::assertEquals(1, $this->sut->count($key, 'rat'));
    }

    public function testCopy(): void
    {
        $this->sut->insertMany('copyFrom', ['cow', 'donkey', 'fish']);
        $this->sut->copy('copyFrom', 'copyTo');
        self::assertTrue($this->sut->exists('copyTo', 'fish'));
        self::assertFalse($this->sut->exists('copyTo', 'monkey'));
    }

    public function testInfoOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->info('nonExistingKey');
    }
}