<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

class IntType extends NumberType
{
    public function getPrecision(array $options): ?int
    {
        return 0;
    }
}
