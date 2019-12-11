<?php

namespace Palicao\PhpRebloom;

class CuckooFilterInfo
{
    /** @var string */
    private $key;

    /** @var int */
    private $size;

    /** @var int */
    private $numBuckets;

    /** @var int */
    private $numFilters;

    /** @var int */
    private $insertedItems;

    /** @var int */
    private $deletedItems;

    /** @var int */
    private $bucketSize;

    /** @var int */
    private $expansionRate;

    /** @var int */
    private $maxIterations;

    /**
     * CuckooFilterInfo constructor.
     * @param string $key
     * @param int $size
     * @param int $numBuckets
     * @param int $numFilters
     * @param int $insertedItems
     * @param int $deletedItems
     * @param int $bucketSize
     * @param int $expansionRate
     * @param int $maxIterations
     */
    public function __construct(
        string $key,
        int $size,
        int $numBuckets,
        int $numFilters,
        int $insertedItems,
        int $deletedItems,
        int $bucketSize,
        int $expansionRate,
        int $maxIterations
    ) {
        $this->key = $key;
        $this->size = $size;
        $this->numBuckets = $numBuckets;
        $this->numFilters = $numFilters;
        $this->insertedItems = $insertedItems;
        $this->deletedItems = $deletedItems;
        $this->bucketSize = $bucketSize;
        $this->expansionRate = $expansionRate;
        $this->maxIterations = $maxIterations;
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
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getNumBuckets(): int
    {
        return $this->numBuckets;
    }

    /**
     * @return int
     */
    public function getNumFilters(): int
    {
        return $this->numFilters;
    }

    /**
     * @return int
     */
    public function getInsertedItems(): int
    {
        return $this->insertedItems;
    }

    /**
     * @return int
     */
    public function getDeletedItems(): int
    {
        return $this->deletedItems;
    }

    /**
     * @return int
     */
    public function getBucketSize(): int
    {
        return $this->bucketSize;
    }

    /**
     * @return int
     */
    public function getExpansionRate(): int
    {
        return $this->expansionRate;
    }

    /**
     * @return int
     */
    public function getMaxIterations(): int
    {
        return $this->maxIterations;
    }
}