<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface HasDefaultValue
{
    public function getDefaultValue(array $entityData, array $typeOptions);
}
