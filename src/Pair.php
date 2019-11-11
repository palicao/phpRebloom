<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

final class Pair
{
    /** @var string */
    private $item;

    /** @var int */
    private $value;

    public function __construct(string $item, int $value)
    {
        $this->item = $item;
        $this->value = $value;
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}