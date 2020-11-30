<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use SeStep\Typeful\Validation\ValidationError;

class SelectionType implements PropertyType, RendersValue
{
    public function renderValue($value, array $options = [])
    {
        return $this->getItems($options)[$value];
    }

    public function validateValue($value, array $options = []): ?ValidationError
    {
        if (!array_key_exists($value, $this->getItems($options))) {
            return new ValidationError(ValidationError::INVALID_VALUE);
        }

        return null;
    }

    public function getItems(array $options): array
    {
        $items = $options['items'];
        if (is_callable($items)) {
            $items = call_user_func($items, $options);
        }
        if (is_iterable($items) && !is_array($items)) {
            $items = iterator_to_array($items);
        }

        return $items;
    }

    public static function enumerateConstants(string $class, string $prefixValues = ''): array
    {
        $classReflection = new \ReflectionClass($class);
        $items = $classReflection->getConstants();
        if ($prefixValues) {
            foreach ($items as $key => $value) {
                $items[$key] = "$prefixValues$value";
            }
        }

        return $items;
    }
}
