<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface SerializesValue
{
    public function serialize($value, array $typeOptions);

    public function deserialize($serialized, array $typeOptions);
}
