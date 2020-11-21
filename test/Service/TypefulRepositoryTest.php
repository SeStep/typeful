<?php declare(strict_types=1);

namespace SeStep\Typeful\Service;

use PHPUnit\Framework\TestCase;
use SeStep\Typeful\Entity\EntityDescriptor;
use SeStep\Typeful\Entity\GenericDescriptor;
use SeStep\Typeful\Entity\Property;
use SeStep\Typeful\TestDoubles\DummyEntity;
use SeStep\Typeful\TestDoubles\DummyTypefulRepository;
use SeStep\Typeful\TestDoubles\RegistryFactory;

class TypefulRepositoryTest extends TestCase
{
    /** @var EntityDescriptor */
    private $entityDescriptor;
    /** @var TypeRegistry */
    private $typeRegistry;

    protected function setUp(): void
    {
        if (!isset($this->typeRegistry)) {
            $this->typeRegistry = RegistryFactory::createTypeRegistry();
        }

        if (!isset($this->entityDescriptor)) {
            $this->entityDescriptor = new GenericDescriptor([
                'os' => new Property('text', [
                    'default' => 'Win',
                ]),
                'version' => new Property('int'),
            ]);
        }
    }

    protected function createTestInstance(): DummyTypefulRepository
    {
        return new DummyTypefulRepository($this->entityDescriptor, $this->typeRegistry);
    }

    public function testCreate()
    {
        $dummy = $this->createTestInstance();
        $result = $dummy->createNewFromTypefulData([
            'version' => 42,
        ]);

        $expectedCreated = ['os' => 'Win', 'version' => 42];
        self::assertEquals($expectedCreated, $result);
        self::assertEquals([$expectedCreated], $dummy->getCreated());
        self::assertEquals([], $dummy->getUpdated());
    }

    public function testUpdateNoChanges()
    {
        $dummy = $this->createTestInstance();

        $entity = new DummyEntity();
        $entity->os = 'Win';
        $entity->version = 42;

        $entity->setAssignGroup('update');
        $result = $dummy->updateWithTypefulData($entity, [
            'os' => 'Win',
            'version' => 42,
        ]);
        self::assertFalse($result);

        self::assertEquals([], $dummy->getCreated());
        self::assertEquals([], $dummy->getUpdated());

        self::assertEquals([], $entity->getAssigns());
    }
}
