<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRebloom\Tests\Integration;

use Palicao\PhpRebloom\TopK;
use Palicao\PhpRebloom\TopKInfo;
use phpDocumentor\Reflection\Types\Void_;

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
        $this->assertTrue($result);
    }

    public function testAdd(): void
    {
        $key = 'addTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $result = $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        $this->assertEquals([false, false, false, 'foo', false, false], $result);
    }

    public function testQuery(): void
    {
        $key = 'queryTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        $result = $this->sut->query($key, 'foo', 'bar', 'baz', 'bom');
        $this->assertEquals([false, true, true, false], $result);
    }

    public function testCount(): void
    {
        $key = 'countTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        $result = $this->sut->count($key, 'foo', 'bar', 'baz', 'bom');
        $this->assertEquals([1, 2, 3, 0], $result);
    }

    public function testList(): void
    {
        $key = 'listTest';
        $this->sut->reserve($key, 2, 10, 10, .925);
        $this->sut->add($key, 'foo', 'bar', 'baz', 'baz', 'bar', 'baz');
        $result = $this->sut->list($key);
        $this->assertEquals(['bar', 'baz'], $result);
    }

    public function testInfo(): void
    {
        $key = 'infoTest';
        $this->sut->reserve($key, 2, 10, 12, .925);
        $result = $this->sut->info($key);
        $expected = new TopKInfo($key, 2, 10, 12, .925);
        $this->assertEquals($expected, $result);
    }
}