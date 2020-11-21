<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use Nette\Schema\Schema;

interface TypeConfigValidation
{
    public static function getConfigSchema(): Schema;
}
