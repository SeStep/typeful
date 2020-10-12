<?php declare(strict_types=1);

namespace SeStep\Typeful\DI;


use Nette\DI\Definitions\ServiceDefinition;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class TypefulPlugins
{
    private $typePlugins = [];
    private $entityPlugins = [];

    public function addTypePlugin(string $configKey, Schema $pluginConfigSchema, string $tag = null)
    {
        static $reservedTypeKeys = ['class', 'arguments', 'autowired', 'service'];
        if (in_array($configKey, $reservedTypeKeys)) {
            throw new InvalidArgumentException("Key '$configKey' is reserved for type definitions");
        }
        if (array_key_exists($configKey, $this->typePlugins)) {
            throw new InvalidStateException("Type plugin '$configKey' already exists ");
        }

        $this->typePlugins[$configKey] = [
            'schema' => $pluginConfigSchema,
            'tag' => $tag ?: $configKey,
        ];
    }

    public function addEntityPlugin(string $configKey, Schema $pluginConfigSchema, string $tag = null)
    {
        static $reservedTypeKeys = ['name', 'propertyNamePrefix', 'properties'];
        if (in_array($configKey, $reservedTypeKeys)) {
            throw new InvalidArgumentException("Key '$configKey' is reserved for entity definitions");
        }
        if (array_key_exists($configKey, $this->entityPlugins)) {
            throw new InvalidStateException("Entity plugin '$configKey' already exists ");
        }

        $this->entityPlugins[$configKey] = [
            'schema' => $pluginConfigSchema,
            'tag' => $tag ?: $configKey,
        ];
    }

    public function getTypefulSchema(): Schema
    {
        return Expect::structure([
            'types' => Expect::arrayOf($this->createTypeSchema()),
            'entities' => Expect::arrayOf($this->createEntitySchema()),
        ]);
    }

    private function createTypeSchema(): Schema
    {
        $typePlugins = [];
        foreach ($this->typePlugins as $configKey => $plugin) {
            $typePlugins[$configKey] = $plugin['schema'];
        }

        return Expect::anyOf(
            Expect::structure(array_merge([
                'class' => Expect::string()->required(),
                'arguments' => Expect::array(),
                'autowired' => Expect::bool(false),
            ], $typePlugins)),
            Expect::structure(array_merge([
                'service' => Expect::string()->assert(function ($value) {
                    return mb_substr($value, 0, 1) === '@';
                }, 'String is in a service reference format'),
                'netteControlFactory' => Expect::mixed(),
            ], $typePlugins)),
        );
    }

    private function createEntitySchema(): Schema
    {
        $entityPlugins = [];
        foreach ($this->typePlugins as $configKey => $plugin) {
            $entityPlugins[$configKey] = $plugin['schema'];
        }

        return Expect::structure(array_merge([
            'name' => Expect::string(),
            'propertyNamePrefix' => Expect::string(),
            'properties' => Expect::arrayOf(Expect::structure([
                'type' => Expect::string()->required(),
                'options' => Expect::array(),
            ]))->min(1.0)
        ], $entityPlugins));
    }

    public function decorateTypeDefinition(ServiceDefinition $typeDefinition, object $typeConfig)
    {
        foreach ($this->typePlugins as $configKey => $plugin) {
            $configPluginValue = $typeConfig->$configKey ?? null;
            if (!$configPluginValue) {
                continue;
            }
            $typeDefinition->addTag($plugin['tag'], $configPluginValue);
        }
    }

    public function decorateEntityDefinition(ServiceDefinition $entityDefinition, object $entityConfig)
    {
        foreach ($this->entityPlugins as $configKey => $plugin) {
            $configPluginValue = $entityConfig->$configKey ?? null;
            if (!$configPluginValue) {
                continue;
            }
            $entityDefinition->addTag($plugin['tag'], $configPluginValue);
        }
    }
}
