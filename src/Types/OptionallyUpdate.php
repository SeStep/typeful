<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface OptionallyUpdate
{
    /**
     * Decides whether value should be updated
     *
     * @param mixed $value value to be persisted
     * @param mixed $persistedValue currently persisted value
     * @param array $typeOptions
     * @return bool
     */
    public function shouldUpdate($value, $persistedValue, array $typeOptions): bool;
}
