<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

class IntType extends NumberType
{
    protected function getPrecision(array $options): ?int
    {
        return 0;
    }
}
