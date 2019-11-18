<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

class TopKInfo
{
    /** @var string */
    private $key;

    /** @var int */
    private $topK;

    /** @var int */
    private $width;

    /** @var int */
    private $depth;

    /** @var float */
    private $decay;

    /**
     * TopKInfo constructor.
     * @param string $key
     * @param int $topK
     * @param int $width
     * @param int $depth
     * @param float $decay
     */
    public function __construct(string $key, int $topK, int $width, int $depth, float $decay)
    {
        $this->key = $key;
        $this->topK = $topK;
        $this->width = $width;
        $this->depth = $depth;
        $this->decay = $decay;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getTopK(): int
    {
        return $this->topK;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return float
     */
    public function getDecay(): float
    {
        return $this->decay;
    }
}