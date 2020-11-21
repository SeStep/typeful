<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface PostStoreCommit
{
    /**
     * Commits value change after it gets persisted
     *
     * @param mixed $value value to be committed
     * @param array $typeOptions
     * @return mixed
     */
    public function commitValue($value, array $typeOptions);
}
