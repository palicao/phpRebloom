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
        $this->assertTrue($this->sut->reserve($key, 200, 10, 20, 2));
        $this->assertTrue((bool)$this->redis->exists($key));

        $info = $this->sut->info($key);
        $this->assertEquals($key, $info->getKey(), 'Wrong key');
        $this->assertEquals(376, $info->getSize(), 'Wrong size');
        $this->assertEquals(10, $info->getBucketSize(), 'Wrong bucket size');
        $this->assertEquals(0, $info->getDeletedItems(), 'Wrong deleted items');
        $this->assertEquals(0, $info->getInsertedItems(), 'Wrong inserted items');
        $this->assertEquals(2, $info->getExpansionRate(), 'Wrong expansion rate');
        $this->assertEquals(20, $info->getMaxIterations(), 'Wrong max iterations');
        $this->assertEquals(32, $info->getNumBuckets(), 'Wrong num buckets');
        $this->assertEquals(1, $info->getNumFilters(), 'Wrong num filters');

    }

    public function testInsert(): void
    {
        $this->sut->insert('insertTest', 'horse');
        $result = $this->sut->exists('insertTest', 'horse');
        $this->assertTrue($result);
    }

    public function testInsertMany(): void
    {
        $key = 'insertManyTest';
        $result = $this->sut->insertMany($key, ['horse', 'cow']);
        $this->assertEquals([true, true], $result);
        $this->assertTrue($this->sut->exists($key, 'horse'));
        $this->assertFalse($this->sut->exists($key, 'monkey'));
    }

    public function testInsertIfKeyExists(): void
    {
        $key = 'testInsertIfKeyExists';
        $this->sut->reserve($key, 10);
        $this->assertTrue($this->sut->insertIfKeyExists($key, 'foo'));
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
        $this->assertTrue($this->sut->exists($key, 'horse'));
        $this->assertFalse($this->sut->exists($key, 'bat'));
        $this->assertTrue($this->sut->exists($key, 'rat'));
    }

    public function testCount(): void
    {
        $key = 'countTest';
        $this->sut->insertMany($key,  ['horse', 'bat', 'rat', 'horse', 'bat', 'horse']);
        $this->assertEquals(3, $this->sut->count($key, 'horse'));
        $this->assertEquals(2, $this->sut->count($key, 'bat'));
        $this->assertEquals(1, $this->sut->count($key, 'rat'));
    }

    public function testCopy(): void
    {
        $this->sut->insertMany('copyFrom', ['cow', 'donkey', 'fish']);
        $this->sut->copy('copyFrom', 'copyTo');
        $this->assertTrue($this->sut->exists('copyTo', 'fish'));
        $this->assertFalse($this->sut->exists('copyTo', 'monkey'));
    }

    public function testInfoOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->info('nonExistingKey');
    }
}