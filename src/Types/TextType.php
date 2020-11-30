<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use SeStep\Typeful\Validation\ValidationError;

class TextType implements PropertyType
{
    public function validateValue($value, array $options = []): ?ValidationError
    {
        return null;
    }
}
