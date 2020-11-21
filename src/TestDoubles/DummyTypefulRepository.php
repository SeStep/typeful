<?php declare(strict_types=1);

namespace SeStep\Typeful\TestDoubles;

use SeStep\Typeful\Entity\EntityDescriptor;
use SeStep\Typeful\Service\TypefulRepository;
use SeStep\Typeful\Service\TypeRegistry;

class DummyTypefulRepository extends TypefulRepository
{
    private $created = [];
    private $updated = [];

    public function __construct(EntityDescriptor $descriptor, TypeRegistry $typeRegistry)
    {
        parent::__construct($descriptor, $typeRegistry,'common.testDummy');
    }

    public function getCreated(): array
    {
        return $this->created;
    }

    public function getUpdated(): array
    {
        return $this->updated;
    }

    protected function createFromData(array $data)
    {
        $this->created[] = $data;
        return $data;
    }

    protected function updateByData($entity, $data): bool
    {
        $this->updated[] = $data;

        return true;
    }
}
