<?php declare(strict_types=1);

namespace SeStep\Typeful\DI;

use Nette\InvalidStateException;

trait RegisterTypeful
{
    protected function registerTypeful(array $typefulConfig)
    {
        /** @var TypefulExtension $typefulExtension */
        $typefulExtensionArr = $this->compiler->getExtensions(TypefulExtension::class) ?? null;
        if (empty($typefulExtensionArr)) {
            throw new InvalidStateException(TypefulExtension::class . ' is not registered');
        }
        /** @var TypefulExtension $typefulExtension */
        $typefulExtension = reset($typefulExtensionArr);
        $typefulExtension->addTypefulModule($this->name, $typefulConfig);
    }
}
