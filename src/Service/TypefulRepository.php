<?php declare(strict_types=1);

namespace SeStep\Typeful\Service;

use Nette\InvalidStateException;
use SeStep\Typeful\Entity\EntityDescriptor;
use SeStep\Typeful\Types\HasDefaultValue;
use SeStep\Typeful\Types\OptionallyUpdate;
use SeStep\Typeful\Types\PostStoreCommit;
use SeStep\Typeful\Types\PreStoreNormalize;
use SeStep\Typeful\Types\SerializesValue;

abstract class TypefulRepository
{
    /** @var EntityDescriptor */
    private $entityDescriptor;
    /** @var TypeRegistry */
    private $typeRegistry;

    public function __construct(EntityDescriptor $entityDescriptor, TypeRegistry $typeRegistry) {
        $this->entityDescriptor = $entityDescriptor;
        $this->typeRegistry = $typeRegistry;
    }

    public function createNewFromTypefulData(array $data)
    {
        $dataWithDefaults = $this->initDefaults($this->entityDescriptor, $data);
        $normalizedData = $this->normalizeValuesBeforeSave($this->entityDescriptor, $dataWithDefaults);
        $serializedData = $this->serializePropertyValues($this->entityDescriptor, $normalizedData);

        $entity = $this->createFromData($serializedData);

        $this->commitValuesAfterSave($this->entityDescriptor, $normalizedData);

        return $entity;
    }

    public function updateWithTypefulData($entity, array $data): bool
    {
        $normalizedData = $this->normalizeValuesBeforeSave($this->entityDescriptor, $data, $entity);
        $serializedData = $this->serializePropertyValues($this->entityDescriptor, $normalizedData);

        if (empty($serializedData)) {
            return false;
        }

        $updateResult = $this->updateByData($entity, $serializedData);

        $this->commitValuesAfterSave($this->entityDescriptor, $normalizedData);

        return $updateResult;
    }


    abstract protected function createFromData(array $data);

    abstract protected function updateByData($entity, $data): bool;


    private function initDefaults(EntityDescriptor $descriptor, array $data)
    {
        foreach ($descriptor->getProperties() as $name => $property) {
            $type = $this->typeRegistry->getType($property->getType());
            $options = $property->getOptions();
            $value = $data[$name] ?? null;
            if (!$value) {
                $value = $property->getDefaultValue($data);
            }
            if (!$value && $type instanceof HasDefaultValue) {
                $value = $type->getDefaultValue($data, $options);
            }

            if (is_string($value) && mb_strpos($value, '_::') === 0) {
                $method = mb_substr($value, 3);
                if (!method_exists($this, $method)) {
                    $self = get_class($this);
                    throw new InvalidStateException("Methond '$method' not found on '$self'");
                }
                $value = $this->$method();
            }

            $data[$name] = $value;
        }

        return $data;
    }

    private function normalizeValuesBeforeSave(EntityDescriptor $descriptor, array $data, $entity = null): array
    {
        $normalizedData = [];
        foreach ($descriptor->getProperties() as $name => $property) {
            $type = $this->typeRegistry->getType($property->getType());
            $options = $property->getOptions();

            if (!isset($data[$name])) {
                if ($entity) {
                    $data[$name] = $entity->$name ?? null;
                }
                continue;
            }

            $shouldUpdate = true;
            if ($entity) {
                $newValue = $data[$name];
                $currentValue = $entity->$name;
                if ($type instanceof OptionallyUpdate) {
                    $shouldUpdate = $type->shouldUpdate($newValue, $currentValue, $options);
                } else {
                    $shouldUpdate = $currentValue !== $newValue;
                }
            }

            if (!$shouldUpdate) {
                continue;
            }

            $value = $data[$name];
            $value = $property->normalizeValue($value);
            if ($type instanceof PreStoreNormalize) {
                // FIXME: normalizePreStore should be using normalized values
                $value = $type->normalizePreStore($value, $options, $data);
            }

            $normalizedData[$name] = $value;
        }

        return $normalizedData;
    }

    private function serializePropertyValues(EntityDescriptor $descriptor, $data): array
    {
        $serialized = [];
        foreach ($descriptor->getProperties() as $name => $property) {
            if (!array_key_exists($name, $data)) {
                continue;
            }
            $value = $data[$name];
            $type = $this->typeRegistry->getType($property->getType());
            $propOptions = $property->getOptions();
            if ($type instanceof SerializesValue) {
                $value = $type->serialize($value, $propOptions);
            }
            $serialized[$name] = $value;
        }

        return $serialized;
    }

    private function commitValuesAfterSave(EntityDescriptor $descriptor, &$data)
    {
        foreach ($descriptor->getProperties() as $name => $property) {
            if (!array_key_exists($name, $data)) {
                continue;
            }
            $value = $data[$name];
            $type = $this->typeRegistry->getType($property->getType());
            $options = $property->getOptions();
            if ($type instanceof PostStoreCommit) {
                $type->commitValue($value, $options);
            }
        }
    }
}
