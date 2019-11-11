<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

final class CountMinSketchInfo
{
    /** @var string */
    private $key;

    /** @var int */
    private $width;

    /** @var int */
    private $depth;

    /** @var int */
    private $count;

    public function __construct(string $key, int $width, int $depth, int $count)
    {
        $this->key = $key;
        $this->width = $width;
        $this->depth = $depth;
        $this->count = $count;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
