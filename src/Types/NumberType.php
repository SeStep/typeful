<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SeStep\Typeful\Validation\ValidationError;

class NumberType implements PropertyType, PreStoreNormalize, TypeConfigValidation, RendersValue
{
    const ERROR_LESS_THAN_MIN = 'typeful.error.lessThanMinimum';
    const ERROR_MORE_THAN_MAX = 'typeful.error.moreThanMaximum';

    public function renderValue($value, array $options = [])
    {
        $round = round($value, $this->getPrecision($options));
        return $round;
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
            $value = round($value, $precision);
        }
        if ($precision <= 0) {
            $value = (int)$value;
        }

        return $value;
    }

    public function getPrecision(array $options): ?int
    {
        return $options['precision'] ?? null;
    }

    public static function getConfigSchema(): Schema
    {
        return Expect::structure([
            'min' => Expect::float(),
            'max' => Expect::float(),
            'precision' => Expect::int(),
        ])->castTo('array');
    }
}
