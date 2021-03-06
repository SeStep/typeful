<?php declare(strict_types=1);

namespace SeStep\Typeful\TestDoubles;

use SeStep\Typeful\Entity\GenericDescriptor;
use SeStep\Typeful\Entity\Property;
use SeStep\Typeful\Service\EntityDescriptorRegistry;
use SeStep\Typeful\Service\TypeRegistry;
use SeStep\Typeful\Types\IntType;
use SeStep\Typeful\Types\TextType;

class RegistryFactory
{
    const TEST_ENTITY_FURNITURE = 'furniture';

    const TEST_TYPE_TEXT = 'text';
    const TEST_TYPE_INT = 'int';

    public static function createEntityRegistry(): EntityDescriptorRegistry
    {
        $furnitureDescriptor = new GenericDescriptor([
            'class' => new Property(self::TEST_TYPE_TEXT),
            'legCount' => new Property(self::TEST_TYPE_INT, [
                'min' => 1,
                'max' => 8,
            ]),
            'description' => new Property(self::TEST_TYPE_TEXT, [
                'required' => false,
                'richText' => true,
            ]),
        ]);

        return new EntityDescriptorRegistry([
            self::TEST_ENTITY_FURNITURE => $furnitureDescriptor,
        ]);
    }

    public static function createTypeRegistry(array $additionalTypes = []): TypeRegistry
    {
        return new TypeRegistry(array_merge([
            self::TEST_TYPE_TEXT => new TextType(),
            self::TEST_TYPE_INT => new IntType(),
        ], $additionalTypes));
    }
}
