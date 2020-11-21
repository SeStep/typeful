<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface HasDefaultValue
{
    /**
     * Returns default value for property
     *
     * Can use type options or data of entity.
     *
     * @param array $typeOptions
     * @param array $entityData
     * @return mixed
     */
    public function getDefaultValue(array $typeOptions, array $entityData);
}
