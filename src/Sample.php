<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

final class Sample
{
    /** @var float */
    private $value;

    /** @var float */
    private $weight;

    public function __construct(float $value, float $weight)
    {
        $this->value = $value;
        $this->weight = $weight;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }
}
