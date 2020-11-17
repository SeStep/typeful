<?php declare(strict_types=1);

namespace SeStep\Typeful\Entity;

use SeStep\Typeful\Types\CommitAwareType;
use SeStep\Typeful\Types\PropertyType;

class Property
{
    /** @var string */
    private $type;
    /** @var array */
    private $options;

    public function __construct(string $type, array $options = [])
    {
        $this->type = $type;
        $this->options = $options;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isRequired(): bool
    {
        return $this->options['required'] ?? true;
    }

    public function getDefaultValue($entityData)
    {
        $defaultValue = $this->options['default'] ?? null;

        if (is_callable($defaultValue)) {
            return call_user_func($defaultValue, $entityData);
        }

        return $defaultValue;
    }

    public function normalizeValue($value)
    {
        if (isset($this->options['normalize'])) {
            return call_user_func($this->options['normalize'], $value);
        }

        return $value;
    }
}
