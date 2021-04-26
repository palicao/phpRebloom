<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

final class TDigestInfo
{
    /** @var int */
    private $compression;

    /** @var int */
    private $capacity;

    /** @var int */
    private $mergedNodes;

    /** @var int */
    private $unmergedNodes;

    /** @var float */
    private $mergedWeight;

    /** @var float */
    private $unmergedWeight;

    /** @var int */
    private $totalCompression;

    /**
     * TDigestInfo constructor.
     * @param int $compression
     * @param int $capacity
     * @param int $mergedNodes
     * @param int $unmergedNodes
     * @param float $mergedWeight
     * @param float $unmergedWeight
     * @param int $totalCompression
     */
    public function __construct(
        int $compression,
        int $capacity,
        int $mergedNodes,
        int $unmergedNodes,
        float $mergedWeight,
        float $unmergedWeight,
        int $totalCompression
    ) {
        $this->compression = $compression;
        $this->capacity = $capacity;
        $this->mergedNodes = $mergedNodes;
        $this->unmergedNodes = $unmergedNodes;
        $this->mergedWeight = $mergedWeight;
        $this->unmergedWeight = $unmergedWeight;
        $this->totalCompression = $totalCompression;
    }

    public function getCompression(): int
    {
        return $this->compression;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getMergedNodes(): int
    {
        return $this->mergedNodes;
    }

    public function getUnmergedNodes(): int
    {
        return $this->unmergedNodes;
    }

    public function getMergedWeight(): float
    {
        return $this->mergedWeight;
    }

    public function getUnmergedWeight(): float
    {
        return $this->unmergedWeight;
    }

    public function getTotalCompression(): int
    {
        return $this->totalCompression;
    }
}
