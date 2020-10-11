<?php declare(strict_types=1);

namespace SeStep\Typeful\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use SeStep\Typeful\Console\ListEntitiesCommand;
use SeStep\Typeful\Console\ListTypesCommand;
use SeStep\Typeful\Entity\GenericDescriptor;
use SeStep\Typeful\Entity\Property;
use SeStep\Typeful\Service;
use SeStep\Typeful\Validation;

class TypefulExtension extends CompilerExtension
{
    use RegisterTypeful;

    const TAG_TYPE = 'typeful.propertyType';
    const TAG_ENTITY = 'typeful.entity';

    /** @var array[] */
    private $moduleConfigs = [];

    public function addTypefulModule(string $name, $typefulModuleConfig)
    {
        $this->moduleConfigs[$name] = $typefulModuleConfig;
    }

    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        $configFile = $this->loadFromFile(__DIR__ . '/typefulExtension.neon');
        $this->registerTypeful($configFile['typeful']);

        $this->loadDefinitionsFromConfig([
            'typeRegister' => Service\TypeRegistry::class,
            'entityDescriptorRegister' => Service\EntityDescriptorRegistry::class,
            'validator' => Validation\TypefulValidator::class,
        ]);


        if (class_exists(\Symfony\Component\Console\Command\Command::class)) {
            $builder->addDefinition($this->prefix('listTypesCommand'))
                ->setType(ListTypesCommand::class)
                ->setArgument('name', $this->name . ':types:list');
            $builder->addDefinition($this->prefix('listDescriptorsCommand'))
                ->setType(ListEntitiesCommand::class)
                ->setArgument('name', $this->name . ':descriptors:list');
        }
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        $this->loadModules($builder);

        $types = [];

        $typesDefinitions = $builder->findByTag(self::TAG_TYPE);
        foreach ($typesDefinitions as $definitionName => $typeName) {
            $definition = $builder->getDefinition($definitionName);
            $types[$typeName] = $definition;
        }

        /** @var ServiceDefinition $typeRegister */
        $typeRegister = $builder->getDefinition($this->prefix('typeRegister'));
        $typeRegister->setArgument('propertyTypes', $types);

        $descriptors = [];
        foreach ($builder->findByTag(self::TAG_ENTITY) as $service => $entityClass) {
            $descriptors[$entityClass] = $builder->getDefinition($service);
        }

        /** @var ServiceDefinition $entityDescriptorRegister */
        $entityDescriptorRegister = $builder->getDefinition($this->prefix('entityDescriptorRegister'));
        $entityDescriptorRegister->setArgument('descriptors', $descriptors);
    }

    private function loadModules(ContainerBuilder $builder)
    {
        $processor = new Processor();
        $typefulModuleSchema = self::getTypefulSchema();

        foreach ($this->moduleConfigs as $name => $moduleConfig) {
            $config = $processor->process($typefulModuleSchema, $moduleConfig);
            $this->loadModule($builder, $name, $config);
        }
    }

    private function loadModule(ContainerBuilder $builder, $name, object $config)
    {
        foreach ($config->types as $type => $definition) {
            if (isset($definition->service)) {
                $service = mb_substr($definition->service, 1);
                $typeDefinition = $builder->getDefinition($service);
            } else {
                $typeDefinition = $builder->addDefinition("$name.type.$type")
                    ->setType($definition->class)
                    ->setAutowired($definition->autowired)
                    ->setArguments($definition->arguments);
            }
            $typeDefinition->addTag(TypefulExtension::TAG_TYPE, "$name.$type");

            // TODO: move this someplace else, possibly plugins?
            if (class_exists(NetteTypefulExtension::class) && isset($definition->netteControlFactory)) {
                $typeDefinition->addTag(NetteTypefulExtension::TAG_TYPE_CONTROL_FACTORY,
                    $definition->netteControlFactory);
            }
        }

        foreach ($config->entities as $entity => $definition) {
            $builder->addDefinition(
                "$name.entity.$entity",
                $this->createEntityDefinition($definition)
                    ->addTag(TypefulExtension::TAG_ENTITY, $definition->name ?? $entity)
            );
        }
    }

    private function createEntityDefinition($definition)
    {
        return (new ServiceDefinition())
            ->setType(GenericDescriptor::class)
            ->setAutowired(false)
            ->setArguments([
                'properties' => self::getPropertiesStatement($definition->properties),
                'propertyNamePrefix' => $definition->propertyNamePrefix ?? '',
            ]);
    }

    protected static function getPropertiesStatement(array $properties): array
    {
        $propertyStatements = [];
        foreach ($properties as $name => $property) {
            $propertyStatements[$name] = new Statement(Property::class,
                ['type' => $property->type, 'options' => $property->options ?? []]);
        }

        return $propertyStatements;
    }

    private function getTypefulSchema()
    {
        $typeSchema = Expect::anyOf(
            Expect::structure([
                'class' => Expect::string()->required(),
                'arguments' => Expect::array(),
                'autowired' => Expect::bool(false),
                'netteControlFactory' => Expect::mixed(),
            ]),
            Expect::structure([
                'service' => Expect::string()->assert(function ($value) {
                    return mb_substr($value, 0, 1) === '@';
                }, 'String is in a service reference format'),
                'netteControlFactory' => Expect::mixed(),
            ]),
        );
        $entitySchema = Expect::structure([
            'name' => Expect::string(),
            'propertyNamePrefix' => Expect::string(),
            'properties' => Expect::arrayOf(Expect::structure([
                'type' => Expect::string()->required(),
                'options' => Expect::array(),
            ]))->min(1.0)
        ]);

        return Expect::structure([
            'types' => Expect::arrayOf($typeSchema),
            'entities' => Expect::arrayOf($entitySchema),
        ]);
    }
}
