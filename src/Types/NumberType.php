<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use SeStep\Typeful\Validation\ValidationError;

class NumberType implements PropertyType, PreStoreNormalize
{
    const ERROR_LESS_THAN_MIN = 'typeful.error.lessThanMinimum';
    const ERROR_MORE_THAN_MAX = 'typeful.error.moreThanMaximum';

    public function renderValue($value, array $options = [])
    {
        return round($value, $this->getPrecision($options));
    }

    public function validateValue($value, array $options = []): ?ValidationError
    {
        if (!is_int($value)) {
            return new ValidationError(ValidationError::INVALID_TYPE);
        }

        if (isset($options['min']) && $value < $options['min']) {
            return new ValidationError(self::ERROR_LESS_THAN_MIN);
        }
        if (isset($options['max']) && $value > $options['max']) {
            return new ValidationError(self::ERROR_MORE_THAN_MAX);
        }

        return null;
    }

    public function normalizePreStore($value, array $options, array $entityData = [])
    {
        $precision = $this->getPrecision($options);
        if (is_int($precision)) {
            $value = round($precision);
        }

        return $value;
    }

    public function getPrecision(array $options): ?int
    {
        return $options['precision'] ?? null;
    }
}
