<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use SeStep\Typeful\Validation\ValidationError;

interface PropertyType
{
    public function validateValue($value, array $options = []): ?ValidationError;
}
