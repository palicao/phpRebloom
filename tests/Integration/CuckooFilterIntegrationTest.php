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

    public function testReserveCreatesKey(): void
    {
        $this->sut->reserve('reserve', 1000);
        $this->assertTrue((bool)$this->redis->exists('reserve'));
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

    public function testInsertIfKeyExistsThrowsExceptionOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->insertIfKeyExists('insert2Test', 'foo');
    }

    public function testInsertManyIfKeyExistThrowsExceptionOnNonExistingKey(): void
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
}