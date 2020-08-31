<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRebloom\Tests\Integration;

use Palicao\PhpRebloom\CountMinSketch;
use Palicao\PhpRebloom\CountMinSketchInfo;
use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use Palicao\PhpRebloom\Pair;

class CountMinSketchIntegrationTest extends IntegrationTestCase
{

    /**
     * @var CountMinSketch
     */
    private $sut;

    public function setUp(): void
    {
        parent::setUp();
        $this->sut = new CountMinSketch($this->redisClient);
    }

    public function testInitByDimensions(): void
    {
        $key = 'initByDim';
        $result = $this->sut->initByDimensions($key, 3000, 40);
        self::assertTrue($result);
        $info = $this->sut->info($key);
        self::assertEquals($key, $info->getKey());
        self::assertEquals(3000, $info->getWidth());
        self::assertEquals(40, $info->getDepth());
    }

    public function testInitByProbability(): void
    {
        $key = 'initByProb';
        $result = $this->sut->initByProbability($key, .001, .01);
        self::assertTrue($result);
        $info = $this->sut->info($key);
        self::assertEquals($key, $info->getKey());
        self::assertEquals(2000, $info->getWidth());
        self::assertEquals(7, $info->getDepth());
    }

    public function testIncrementBy(): void
    {
        $key = 'incrementByTest';
        $this->sut->initByDimensions($key, 3000, 40);
        $result1 = $this->sut->incrementBy($key, new Pair('a', 100), new Pair('b', 200));
        $result2 = $this->sut->incrementBy($key, new Pair('a', 20), new Pair('b', 10));

        self::assertTrue($result1);
        self::assertTrue($result2);

        $expected = [new Pair('a', 120), new Pair('b', 210)];
        self::assertEquals($expected, $this->sut->query($key, 'a', 'b'));
    }

    public function testIncrementByOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->incrementBy('nonExistingKey', new Pair('item', 1));
    }

    public function testQueryOnNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->query('nonExistingKey', 'item');
    }

    public function testMerge(): void
    {
        $this->sut->initByDimensions('source1', 3000, 40);
        $this->sut->incrementBy('source1', new Pair('a', 10), new Pair('b', 20));

        $this->sut->initByDimensions('source2', 3000, 40);
        $this->sut->incrementBy('source2', new Pair('a', 20), new Pair('c', 30));

        $this->sut->initByDimensions('destination', 3000, 40);
        $result = $this->sut->merge('destination', ['source1' => 3, 'source2' => 5]);

        self::assertTrue($result);

        $expected = [new Pair('a', 130), new Pair('b', 60), new Pair('c', 150)];
        self::assertEquals($expected, $this->sut->query('destination', 'a', 'b', 'c'));
    }

    public function testMergeNonExistingKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->sut->merge('dest', ['src' => 1]);
    }

    public function testInfo(): void
    {
        $key = 'infoTest';
        $this->sut->initByDimensions($key, 3000, 40);
        $this->sut->incrementBy($key, new Pair('a', 10), new Pair('b', 20));

        $expected = new CountMinSketchInfo($key, 3000, 40, 30);
        $result = $this->sut->info($key);

        self::assertEquals($expected, $result);
    }
}
