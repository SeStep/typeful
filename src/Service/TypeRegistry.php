<?php declare(strict_types=1);

namespace SeStep\Typeful\Service;

use SeStep\Typeful\Types\PropertyType;

class TypeRegistry
{
    /** @var PropertyType[] */
    private $propertyTypes;

    /**
     * @param PropertyType[] $propertyTypes associative array of types
     */
    public function __construct(array $propertyTypes)
    {
        $this->propertyTypes = $propertyTypes;
    }

    public function hasType(string $type): bool
    {
        return isset($this->propertyTypes[$type]);
    }

    public function getType(string $type): ?PropertyType
    {
        if (!isset($this->propertyTypes[$type])) {
            trigger_error("Property type '$type' is not recognized");
            return null;
        }

        return $this->propertyTypes[$type];
    }

    public function getTypesLocalized()
    {
        $types = array_keys($this->propertyTypes);
        return array_combine($types, $types);
    }
}
