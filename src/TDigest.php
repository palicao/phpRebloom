<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

use Palicao\PhpRebloom\Exception\KeyNotFoundException;
use RedisException;

final class TDigest extends BaseFrequencyCounter
{
    /**
     * @param string $key
     * @param int $compression
     * @return bool
     * @throws RedisException
     */
    public function create(string $key, int $compression): bool
    {
        return $this->parseResult($this->client->executeCommand(['TDIGEST.CREATE', $key, $compression]));
    }

    /**
     * @param string $key
     * @return bool
     * @throws RedisException
     */
    public function reset(string $key): bool
    {
        return (bool)$this->client->executeCommand(['TDIGEST.RESET', $key]);
    }

    /**
     * @param string $key
     * @param Sample $firstSample
     * @param Sample ...$otherSamples
     * @return bool
     * @throws RedisException
     */
    public function add(string $key, Sample $firstSample, Sample ... $otherSamples): bool
    {
        $params = ['TDIGEST.ADD', $key, $firstSample->getValue(), $firstSample->getWeight()];
        foreach ($otherSamples as $sample) {
            $params[] = $sample->getValue();
            $params[] = $sample->getWeight();
        }
        return (bool)$this->client->executeCommand($params);
    }

    /**
     * @param string $toKey
     * @param string $fromKey
     * @return bool
     * @throws RedisException
     */
    public function merge(string $toKey, string $fromKey): bool
    {
        return (bool)$this->client->executeCommand(['TDIGEST.MERGE', $toKey, $fromKey]);
    }

    /**
     * @param string $key
     * @return float
     * @throws RedisException
     */
    public function min(string $key): float
    {
        return $this->getResult('TDIGEST.MIN', $key);
    }

    /**
     * @param string $key
     * @return float
     * @throws RedisException
     */
    public function max(string $key): float
    {
        return $this->getResult('TDIGEST.MAX', $key);
    }

    /**
     * @param string $key
     * @param float $quantile
     * @return float
     * @throws RedisException
     */
    public function quantile(string $key, float $quantile): float
    {
        return $this->getResult('TDIGEST.QUANTILE', $key, $quantile);

    }

    /**
     * @param string $key
     * @param float $value
     * @return float
     * @throws RedisException
     */
    public function cdf(string $key, float $value): float
    {
        return $this->getResult('TDIGEST.CDF', $key, $value);
    }

    /**
     * @param string $command
     * @param string $key
     * @param float|null $param
     * @return float
     * @throws RedisException
     */
    private function getResult(string $command, string $key, ?float $param = null): float
    {
        $params = [$command, $key];
        if ($param !== null) {
            $params[] = $param;
        }

        $result = $this->client->executeCommand($params);
        if ($result === false) {
            throw new KeyNotFoundException(sprintf('Key %s does not exist', $key));
        }
        return (float)$result;
    }

    /**
     * @param string $key
     * @return TDigestInfo
     * @throws RedisException
     */
    public function info(string $key): TDigestInfo
    {
        $result = $this->client->executeCommand(['TDIGEST.INFO', $key]);
        if (!is_array($result)) {
            throw new KeyNotFoundException(sprintf('Key %s does not exist', $key));
        }

        return new TDigestInfo(
            (int)$result[1],
            (int)$result[3],
            (int)$result[5],
            (int)$result[7],
            (float)$result[9],
            (float)$result[11],
            (int)$result[13]
        );
    }
}
