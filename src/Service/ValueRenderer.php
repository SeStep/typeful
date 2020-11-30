<?php declare(strict_types=1);

namespace SeStep\Typeful\Service;

use Nette\InvalidStateException;
use SeStep\Typeful\Types\PropertyType;
use SeStep\Typeful\Types\RendersValue;

class ValueRenderer
{
    private $renderers;

    public function __construct()
    {
        $this->renderers = [
            'typeRender' => \Closure::fromCallable([$this, 'typeRender']),
        ];
    }

    public function addRenderer(string $name, $callback): self
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException("Parameter \$callback is not a callable");
        }

        $this->renderers[$name] = $callback;

        return $this;
    }

    public function render($value, PropertyType $type, array $options)
    {
        $renderers = $options['render'] ?? null;
        if (!is_array($renderers)) {
            if ($renderers) {
                throw new InvalidStateException("MisConfigured options[render], expected array or nothing");
            }
            $renderers = $type instanceof RendersValue ? ['typeRender'] : [];
        }

        foreach ($renderers as $i => $render) {
            if (is_string($render) && array_key_exists($render, $this->renderers)) {
                $value = call_user_func($this->renderers[$render], $value, $type, $options);
            } elseif (is_callable($render)) {
                $value = call_user_func($render, $value, $type, $options);
            } else {
                throw new InvalidStateException("MisConfigured beforeRender, got: " . get_debug_type($value));
            }
        }

        return $value;
    }

    private function typeRender($value, RendersValue $type, array $options)
    {
        return $type->renderValue($value, $options);
    }
}
