<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface OptionallyUpdate
{
    public function shouldUpdate($value, array $typeOptions);
}
