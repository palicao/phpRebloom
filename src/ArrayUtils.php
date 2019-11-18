<?php
declare(strict_types=1);

namespace Palicao\PhpRebloom;

class ArrayUtils
{
    /**
     * @param array $result
     * @return bool[]
     */
    public static function toBool(array $result): array
    {
        return array_map('boolval', $result);
    }
}
