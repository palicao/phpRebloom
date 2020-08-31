<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRebloom\Tests\Integration;

use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use Palicao\PhpRebloom\Pair;
use Palicao\PhpRebloom\TopK;

class TopKIntegrationTest extends IntegrationTestCase
{
    /**
     * @var TopK
     */
    private $sut;

    public function setUp(): void
    {
        parent::setUp();
        $this->sut = new TopK($this->redisClient);
    }

    public function testReserve(): void
    {
        $result = $this->sut->reserve('testReserve', 50, 2000, 7, .925);
        self::assertTrue($result);
    }

    public function testAdd(): void
    {
        $key = 'addTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $result = $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        self::assertEquals([false, false, false, 'foo', false, false], $result);
    }

    public function testIncrementBy(): void
    {
        $key = 'incrementByTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $result1 = $this->sut->incrementBy(
            $key,
            new Pair('foo', 10),
            new Pair('bar', 10)
        );
        self::assertEquals([false, false], $result1);

        $result2 = $this->sut->incrementBy(
            $key,
            new Pair('bar', 10),
            new Pair('baz', 25),
            new Pair('nope', 1)
        );
        self::assertEquals([false, 'foo', false], $result2);
    }

    public function testAddOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->add('addTest2', 'test');
    }

    public function testIncrementByOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->incrementBy('foobar', new Pair('test', 10));
    }

    public function testQuery(): void
    {
        $key = 'queryTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        $result = $this->sut->query($key, 'foo', 'bar', 'baz', 'bom');
        self::assertEqualsCanonicalizing(['bar', 'baz'], $result);
    }

    public function testQueryNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->query('queryNonExisting', 'item1');
    }

    public function testCount(): void
    {
        $key = 'countTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        $result = $this->sut->count($key, 'foo', 'bar', 'baz', 'bom');
        self::assertEqualsCanonicalizing([
            new Pair('foo', 1),
            new Pair('bar', 2),
            new Pair('baz', 3),
            new Pair('bom', 0),
        ], $result);
    }

    public function testCountNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->count('nonExistingKey', 'foo');
    }

    public function testList(): void
    {
        $key = 'listTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        $result = $this->sut->list($key);
        self::assertEqualsCanonicalizing([new Pair('bar', 0), new Pair('baz', 1)], $result);
    }

    public function testListNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->list('nonExistingKey');
    }

    public function testInfo(): void
    {
        $key = 'infoTest';
        $this->sut->reserve($key, 2, 10, 12, .925);
        $result = $this->sut->info($key);
        self::assertEquals($key, $result->getKey());
        self::assertEquals(2, $result->getTopK());
        self::assertEquals(10, $result->getWidth());
        self::assertEquals(12, $result->getDepth());
        self::assertEquals(.925, $result->getDecay());
    }

    public function testInfoOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->info('nonExistingKey');
    }
}