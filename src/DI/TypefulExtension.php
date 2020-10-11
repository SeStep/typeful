<?php declare(strict_types=1);

namespace SeStep\Typeful\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
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

    /** @var TypefulPlugins */
    private $plugins;

    public function __construct()
    {
        $this->plugins = new TypefulPlugins();
    }

    public function addTypefulModule(string $name, $typefulModuleConfig)
    {
        $this->moduleConfigs[$name] = $typefulModuleConfig;
    }

    public function addTypePlugin(string $configKey, Schema $pluginConfigSchema, string $tag = null): self
    {
        $this->plugins->addTypePlugin($configKey, $pluginConfigSchema, $tag);
        return $this;
    }

    public function addDescriptorPlugin(string $configKey, Schema $pluginConfigSchema, string $tag = null): self
    {
        $this->plugins->addDescriptorPlugin($configKey, $pluginConfigSchema, $tag);
        return $this;
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
        $typefulModuleSchema = $this->plugins->getTypefulSchema();

        foreach ($this->moduleConfigs as $name => $moduleConfig) {
            $config = $processor->process($typefulModuleSchema, $moduleConfig);
            $this->loadModule($builder, $name, $config);
        }
    }

    private function loadModule(ContainerBuilder $builder, $name, object $config)
    {
        foreach ($config->types as $type => $typeConfig) {
            if (isset($typeConfig->service)) {
                $service = mb_substr($typeConfig->service, 1);
                $typeDefinition = $builder->getDefinition($service);
            } else {
                $typeDefinition = $builder->addDefinition("$name.type.$type")
                    ->setType($typeConfig->class)
                    ->setAutowired($typeConfig->autowired)
                    ->setArguments($typeConfig->arguments);
            }
            $typeDefinition->addTag(TypefulExtension::TAG_TYPE, "$name.$type");
            $this->plugins->decorateTypeDefinition($typeDefinition, $typeConfig);
        }

        foreach ($config->entities as $entity => $entityConfig) {
            $entityDefinition = $builder->addDefinition("$name.entity.$entity")
                ->setType(GenericDescriptor::class)
                ->setAutowired(false)
                ->setArguments([
                    'properties' => self::getPropertiesStatement($entityConfig->properties),
                    'propertyNamePrefix' => $entityConfig->propertyNameConfig ?? '',
                ]);
            $entityDefinition->addTag(TypefulExtension::TAG_ENTITY, $typeConfig->name ?? $entity);
            $this->plugins->decorateEntityDefinition($entityDefinition, $entityConfig);
        }
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

}
