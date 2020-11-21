<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface PreStoreNormalize
{
    /**
     * Normalizes value before store
     *
     * @param mixed $value - value to be normalized
     * @param array $options - type options possibly configuring normalization
     * @param array $entityData - entity data supporting normalization
     * @return mixed - normalized value
     */
    public function normalizePreStore($value, array $options, array $entityData = []);
}
