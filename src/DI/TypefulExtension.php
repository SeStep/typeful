<?php declare(strict_types=1);

namespace SeStep\Typeful\DI;

use Contributte\Console\DI\ConsoleExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException as NetteValidationException;
use Nette\Utils\ObjectHelpers;
use SeStep\Typeful\Console\ListEntitiesCommand;
use SeStep\Typeful\Console\ListTypesCommand;
use SeStep\Typeful\Entity\GenericDescriptor;
use SeStep\Typeful\Entity\Property;
use SeStep\Typeful\Service;
use SeStep\Typeful\Types\TypeConfigValidation;
use SeStep\Typeful\Validation;
use SeStep\Typeful\Validation\ValidationException;

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

    public function addEntityPlugin(string $configKey, Schema $pluginConfigSchema, string $tag = null): self
    {
        $this->plugins->addEntityPlugin($configKey, $pluginConfigSchema, $tag);
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

        if (class_exists(ConsoleExtension::class)) {
            $builder->addDefinition($this->prefix('listTypesCommand'))
                ->setType(ListTypesCommand::class)
                ->addTag(ConsoleExtension::COMMAND_TAG, $this->name . ':types:list')
                ->setArgument('name', $this->name . ':types:list');
            $builder->addDefinition($this->prefix('listDescriptorsCommand'))
                ->setType(ListEntitiesCommand::class)
                ->addTag(ConsoleExtension::COMMAND_TAG, $this->name . ':descriptors:list')
                ->setArgument('name', $this->name . ':descriptors:list');
        }
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        $this->loadModules($builder);
        $this->bindTypefulServicesBeforeCompile($builder);
    }

    private function loadModules(ContainerBuilder $builder)
    {
        $processor = new Processor();
        $typefulModuleSchema = $this->plugins->getTypefulSchema();

        // Use individual cycles to remove significance of extension registration order
        $configs = [];
        foreach ($this->moduleConfigs as $moduleName => $moduleConfig) {
            $configs[$moduleName] = $processor->process($typefulModuleSchema, $moduleConfig);
        }
        foreach (array_keys($this->moduleConfigs) as $moduleName) {
            $this->loadModuleTypes($builder, $moduleName, $configs[$moduleName]->types);
        }
        foreach (array_keys($this->moduleConfigs) as $moduleName) {
            $this->loadModuleEntities($builder, $moduleName, $configs[$moduleName]->entities, $processor);
        }
    }

    private function loadModuleTypes(ContainerBuilder $builder, string $moduleName, array $types)
    {
        foreach ($types as $type => $typeConfig) {
            if (isset($typeConfig->service)) {
                $service = mb_substr($typeConfig->service, 1);
                $typeDefinition = $builder->getDefinition($service);
            } else {
                $typeDefinition = $builder->addDefinition($this->prefix("type.$moduleName.$type"))
                    ->setType($typeConfig->class)
                    ->setAutowired($typeConfig->autowired)
                    ->setArguments($typeConfig->arguments);
            }
            $typeDefinition->addTag(TypefulExtension::TAG_TYPE, "$moduleName.$type");
            $this->plugins->decorateTypeDefinition($typeDefinition, $typeConfig);
        }
    }

    private function loadModuleEntities(
        ContainerBuilder $builder,
        string $moduleName,
        array $entities,
        Processor $processor
    ) {
        foreach ($entities as $entity => $entityConfig) {
            $entityName = $this->prefix("entity.$moduleName.$entity");

            $properties = [];
            foreach ($entityConfig->properties as $name => $property) {
                try {
                    $typeDefinition = $this->getPropertyTypeDefinition($builder, $property->type);
                    $properties[$name] = $this->getPropertyStatement($builder, $property, $typeDefinition, $processor);
                } catch (NetteValidationException|ValidationException $ex) {
                    if ($ex instanceof ValidationException) {
                        $errors = $ex->getErrors();
                    } else {
                        // TODO: When new nette/schema is published, use getErrors() instead of getMessages()
                        $errors = [
                            new Validation\ValidationError('schema-error', ['messages' => $ex->getMessages()]),
                        ];
                    }

                    throw new ValidationException("Failed to initialize property '$entityName#$name'", ...$errors);
                }
            }

            $entityDefinition = $builder->addDefinition($entityName)
                ->setType(GenericDescriptor::class)
                ->setAutowired(false)
                ->setArguments([
                    'properties' => $properties,
                    'propertyNamePrefix' => $entityConfig->propertyNamePrefix ?? '',
                ]);
            $entityName = $typeConfig->name ?? null;
            if (!$entityName) {
                $entityName = "$moduleName.$entity";
            }
            $entityDefinition->addTag(TypefulExtension::TAG_ENTITY, $entityName);
            $this->plugins->decorateEntityDefinition($entityDefinition, $entityConfig);
        }
    }

    private function getPropertyTypeDefinition(ContainerBuilder $builder, string $type): Definition
    {
        $availableTypes = array_flip($builder->findByTag(self::TAG_TYPE));

        if (array_key_exists($type, $availableTypes)) {
            return $builder->getDefinition($availableTypes[$type]);
        }

        $errorData = ['given' => $type];
        if (class_exists(ObjectHelpers::class)) {
            $suggestion = ObjectHelpers::getSuggestion(array_keys($availableTypes), $type);
            if ($suggestion) {
                $errorData['closestMatch'] = $suggestion;
            } else {
                $errorData['availableTypes'] = array_keys($availableTypes);
            }
        }

        throw new ValidationException("Unknown type",
            new Validation\ValidationError(Validation\ValidationError::INVALID_TYPE, $errorData),
        );
    }

    protected function getPropertyStatement(
        ContainerBuilder $builder,
        object $property,
        Definition $typeDefinition,
        Processor $processor
    ): Statement {
        $type = $typeDefinition->getType();

        $options = [];
        foreach ($property->options as $key => $optValue) {
            $options[$key] = Helpers::expand($optValue, $builder->parameters);
        }

        if (is_a($type, TypeConfigValidation::class, true)) {
            $options = $processor->process($type::getConfigSchema(), $options);
        }

        if (!is_array($options)) {
            throw new ValidationException('invalid-return-type',
                Validation\ValidationError::invalidType(['array'], $options),
            );
        }

        return new Statement(Property::class, [
            'type' => $property->type,
            'options' => $options,
        ]);
    }

    protected function bindTypefulServicesBeforeCompile(ContainerBuilder $builder)
    {
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

}
