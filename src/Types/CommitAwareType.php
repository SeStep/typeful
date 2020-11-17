<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

interface CommitAwareType
{
    public function normalizePreCommit($value, array $typeOptions, array $entityData = []);

    public function commitValue($value, array $typeOptions);
}
