<?php declare(strict_types=1);

namespace SeStep\Typeful\DI;

use Nette\InvalidStateException;
use Nette\Schema\Schema;

trait RegisterTypeful
{
    protected function registerTypeful(array $typefulConfig)
    {
        $this->getTypefulExtension()->addTypefulModule($this->name, $typefulConfig);
    }

    protected function registerTypefulTypePlugin(string $configKey, Schema $pluginConfigSchema, string $tag = null)
    {
        $this->getTypefulExtension()->addTypePlugin($configKey, $pluginConfigSchema, $tag);
    }

    protected function registerTypefulDescriptorPlugin(string $configKey, Schema $pluginConfigSchema, string $tag = null)
    {
        $this->getTypefulExtension()->addDescriptorPlugin($configKey, $pluginConfigSchema, $tag);
    }

    private function getTypefulExtension(): TypefulExtension
    {
        /** @var TypefulExtension $typefulExtension */
        $typefulExtensionArr = $this->compiler->getExtensions(TypefulExtension::class) ?? null;
        if (empty($typefulExtensionArr)) {
            throw new InvalidStateException(TypefulExtension::class . ' is not registered');
        }
        return reset($typefulExtensionArr);
    }
}
