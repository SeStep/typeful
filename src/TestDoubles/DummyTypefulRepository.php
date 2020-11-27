<?php declare(strict_types=1);

namespace SeStep\Typeful\TestDoubles;

use SeStep\Typeful\Service\TypefulRepository;

class DummyTypefulRepository extends TypefulRepository
{
    private $created = [];
    private $updated = [];

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
