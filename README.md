# PhpRebloom

Use [Redis Bloom](https://oss.redislabs.com/redisbloom/) with PHP!

[![Code Climate maintainability](https://img.shields.io/codeclimate/coverage-letter/palicao/phpRebloom?label=maintainability&logo=code-climate)](https://codeclimate.com/github/palicao/phpRebloom)
[![Code Climate coverage](https://img.shields.io/codeclimate/coverage/palicao/phpRebloom?logo=code-climate)](https://codeclimate.com/github/palicao/phpRebloom)
[![Build Status](https://travis-ci.com/palicao/phpRebloom.svg?branch=master)](https://travis-ci.com/palicao/phpRebloom)
[![Latest Stable Version](https://img.shields.io/packagist/v/palicao/php-rebloom.svg)](https://packagist.org/packages/palicao/php-rebloom)
[![PHP version](https://img.shields.io/packagist/php-v/palicao/php-rebloom/0.1.0)]((https://packagist.org/packages/palicao/php-rebloom))
[![GitHub](https://img.shields.io/github/license/palicao/phpRebloom)](https://github.com/palicao/phpRebloom/blob/master/LICENSE)

Disclaimer: this is a very lightweight library. For a battery-included experience,
please checkout: https://github.com/averias/phpredis-bloom

## Install
`composer require palicao/php-rebloom`

## Bloom Filter

A Bloom filter is a space-efficient probabilistic data structure designed to determine
whether an element is present in a set. False positives are possible.

```
$bloomFilter = new BloomFilter(
    new RedisClient(
        new Redis(),
        new RedisConnectionParams($host, $port)
    )
);
```

### `reserve`

`BloomFilter::reserve(string $key, float $error, int $capacity): bool`

Creates an empty Bloom Filter with a given desired error ratio and initial capacity.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfreserve.

### `insert`

`BloomFilter::insert(string $key, string $value, ?float $error = null, ?int $capacity = null): bool`

Adds an item to the Bloom Filter, creating the filter if it does not yet exist.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfadd and https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfinsert.

### `insertMany`

`BloomFilter::insertMany(string $key, array $values, ?float $error = null, ?int $capacity = null): bool[]`

Adds several items to the BloomFilter, creating the filter if it does not yet exist.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfmadd and https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfinsert.

### `insertIfKeyExists`

`BloomFilter::insertIfKeyExists(string $key, string $value): bool`

Adds an item to the Bloom Filter, only if the filter already exists.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfadd and https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfinsert.

### `insertManyIfKeyExists`

`BloomFilter::insertManyIfKeyExists(string $key, array $values): bool[]`

Adds several items to the Bloom Filter, only if the filter already exists.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfadd and https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfinsert.

### `exists`

`BloomFilter::exists(string $key, string $value): bool`

Checks if an item exists.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfadd.

### `manyExist`

`BloomFilter::manyExist(string $key, array $values): bool[]`

Checks if many items exist.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfmexists.

### `scanDump`, `loadChunks` and `copy`

`BloomFilter::scanDump(string $key): array`
`BloomFilter::loadChunks(string $key, array $chunks): void`
`BloomFilter::copy(string $sourceKey, string $destKey): void`

`scanDump` exports the whole Bloom Filter in an array, which can be loaded in chunks by
`loadChunks`. The `copy` function, using the previous 2 functions, allows to quickly
copy one Bloom Filter to a different key.

See https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfscandump and https://oss.redislabs.com/redisbloom/Bloom_Commands/#bfloadchunk.

## Cuckoo Filter

Cuckoo filter is similar to Bloom Filter, but it's even more space-efficient and allows deleting items.

```
$cuckooFilter = new CuckooFilter(
    new RedisClient(
        new Redis(),
        new RedisConnectionParams($host, $port)
    )
);
```

### `reserve`

`CuckooFilter::reserve(string $key, int $capacity, ?int $bucketSize = null, ?int $maxIterations = null, ?int $expansion = null): bool`

Create an empty cuckoo filter with an initial capacity. The false positive rate is fixed at about 3%, depending on how full the filter is.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfreserve.

### `insert`

`CuckooFilter::insert(string $key, string $value, bool $allowDuplicateValues = true, ?int $capacity = null): bool`

Adds an item to the cuckoo filter, creating the filter if it does not exist.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfadd, 
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfaddnx,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsert,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsertnx.

### `insertMany`

`CuckooFilter::insertMany(string $key, array $values, bool $allowDuplicateValues = true, ?int $capacity = null): bool[]`

Similar to the previous, adds many values to the key.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfadd, 
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfaddnx,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsert,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsertnx.

### `insertIfKeyExists`

`CuckooFilter::insertIfKeyExists(string $key, string $value, bool $allowDuplicateValues = true, ?int $capacity = null): bool`

Inserts an item in a cuckoo filter, only if it exists.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfadd, 
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfaddnx,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsert,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsertnx.

### `insertManyIfKeyExists`

`CuckooFilter::insertManyIfKeyExists(string $key, array $values, bool $allowDuplicateValues = true, ?int $capacity = null): bool[]`

Inserts many items in a cuckoo filter, only if it exists.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfadd, 
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfaddnx,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsert,
https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfinsertnx.

### `exists`

`CuckooFilter::exists(string $key, string $value): bool`

Returns true if a cuckoo filter exists.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfexists.

### `delete`

`CuckooFilter::delete(string $key, string $value): bool`

Deletes an item once in a filter.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfdel.

### `count`

`CuckooFilter::count(string $key, string $value): int`

Returns the number of times an item may be in the filter.

See https://oss.redislabs.com/redisbloom/Cuckoo_Commands/#cfcount.

### `scanDump`, `loadChunks` and `copy`

`CuckooFilter::scanDump(string $key): array`
`CuckooFilter::loadChunks(string $key, array $chunks): void`
`CuckooFilter::copy(string $sourceKey, string $destKey): void`

`scanDump` exports the whole Cuckoo Filter in an array, which can be loaded in chunks by
`loadChunks`. The `copy` function, using the previous 2 functions, allows to quickly
copy one Cuckoo Filter to a different key.

## CountMinSketch

Count-Min Sketch is a probabilistic data structure that serves as a frequency table of events in a stream of data. 

```
$countMinSketch = new CountMinSketch(
    new RedisClient(
        new Redis(),
        new RedisConnectionParams($host, $port)
    )
);
```

### `initByDimensions`

`CountMinSketch::initByDimensions(string $key, int $width, int $depth): bool`

Initializes a CountMinSketch named `$key` with the `$width` and `$depth` provided by the user.

See https://oss.redislabs.com/redisbloom/CountMinSketch_Commands/#cmsinitbydim.

### `initByProbability`

`CountMinSketch::initByProbability(string $key, float $error, float $probability): bool`

Initializes a CountMinSketch to accommodate the desired error rate and probability for inflated count. 

See https://oss.redislabs.com/redisbloom/CountMinSketch_Commands/#cmsinitbyprob.

### `incrementBy`

`CountMinSketch::incrementBy(string $key, Pair ...$pairs): bool`

Increments one or more items by a given value in a CountMinSketch. A `Pair` represents an item and the value we want to increment.

See https://oss.redislabs.com/redisbloom/CountMinSketch_Commands/#cmsincrby.

### `query`

`CountMinSketch::query(string $key, string ... $items): Pair[]`

Returns the approximate count for one or more items.

See https://oss.redislabs.com/redisbloom/CountMinSketch_Commands/#cmsquery.

### `merge`

Merges multiple CountMinSketches into one, so that the value for each item is the sum
of the values in each sketch. The `$sourceKeysWeightMap` is an associative array
where each key is a sketch, and the value is the weight, that is the value we want
each item count to be multiplied by before merging.

`CountMinSketch::merge(string $destinationKey, array $sourceKeysWeightMap) : bool`

See https://oss.redislabs.com/redisbloom/CountMinSketch_Commands/#cmsmerge.

### `info`

Returns an instance of CountMinSketchInfo, which contains information regarding width, depth and total count of the sketch.

`CountMinSketch::info(string $key): CountMinSketchInfo`

See https://oss.redislabs.com/redisbloom/CountMinSketch_Commands/#cmsinfo.

## TopK

Similar to CountMinSketch, is based on the algorithm described here: https://www.usenix.org/conference/atc18/presentation/gong

### `reserve`

`TopK::reserve(string $key, int $topK, int $width, int $depth, float $decay): bool`

Reserves a TopK suitable to calculate `$topK` top elements, with a given `$width` and `$depth` and with 
a specified `$decay` (probability of reducing a counter in an occupied bucket).

See https://oss.redislabs.com/redisbloom/TopK_Commands/#topkreserve.

### `add`

Adds one or more item to the TooK. If an item enters the Top-K list, the item which is expelled is returned in the position
that was occupied by the added item that took its place in the Top-K.

`TopK::add(string $key, string ... $items): array`

See https://oss.redislabs.com/redisbloom/TopK_Commands/#topkadd.

### `incrementBy`

Increase the score of an item in the data structure by increment. Similar to `add`, expelled items are returned.

`TopK::incrementBy(string $key, Pair ...$pairs): array`

See https://oss.redislabs.com/redisbloom/TopK_Commands/#topkincrby.

### `query`

Returns subset of $items containing the elements found in the Top-K.

`TopK::query(string $key, string ... $items): string[]`

See https://oss.redislabs.com/redisbloom/TopK_Commands/#topkquery.

### `count`

Returns a subset of $items containing the elements found in the top-k with their approximate count.

`TopK::count(string $key, string ... $items): Pair[]`

See https://oss.redislabs.com/redisbloom/TopK_Commands/#topkcount.

### `list`

Returns the top-k items with their relative position.

`TopK::list(string $key): Pair[]`

See https://oss.redislabs.com/redisbloom/TopK_Commands/#topklist.

### `info`

Returns a TopKInfo object containing information about size, with, depth and decay of the Top-K

`TopK::info(string $key): TopKInfo`

See https://oss.redislabs.com/redisbloom/TopK_Commands/#topkinfo.