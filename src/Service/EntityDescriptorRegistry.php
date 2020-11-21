<?php declare(strict_types=1);

namespace SeStep\Typeful\Service;

use Nette\InvalidStateException;
use Nette\Utils\ObjectHelpers;
use SeStep\Typeful\Entity;

class EntityDescriptorRegistry
{
    /** @var Entity\EntityDescriptor[] */
    private $descriptors = [];

    public function __construct(array $descriptors)
    {
        foreach ($descriptors as $name => $descriptor) {
            $this->add($name, $descriptor);
        }
    }

    public function getEntityDescriptor(string $entityName, bool $need = false): ?Entity\EntityDescriptor
    {
        $entityDescriptor = $this->descriptors[$entityName] ?? null;
        if (!$entityDescriptor && $need) {
            $message = "Entity descriptor '$entityName' not found";
            if ($suggestion = $this->getSuggestion(array_keys($this->descriptors), $entityName)) {
                $message .= ", did you mean '$suggestion'?";
            }

            throw new InvalidStateException($message);
        }

        return $entityDescriptor;
    }

    public function getEntityProperty(string $entityName, string $propertyName): ?Entity\Property
    {
        $descriptor = $this->getEntityDescriptor($entityName, true);

        return $descriptor->getProperty($propertyName);
    }

    /**
     * @return Entity\EntityDescriptor[]
     */
    public function getDescriptors(): array
    {
        return $this->descriptors;
    }

    private function add(string $entityName, Entity\EntityDescriptor $descriptor): void
    {
        if (isset($this->descriptors[$entityName])) {
            throw new InvalidStateException("Entity descriptor '$entityName' is already registered");
        }

        $this->descriptors[$entityName] = $descriptor;
    }

    private function getSuggestion(array $options, $value): ?string
    {
        if (!class_exists(ObjectHelpers::class)) {
            return null;
        }

        return ObjectHelpers::getSuggestion($options, $value);
    }
}
