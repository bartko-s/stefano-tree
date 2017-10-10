<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Validator;

interface ValidatorInterface
{
    /**
     * Check if tree indexes, levels is not corrupted.
     *
     * @param int|string $rootNodeId
     *
     * @return bool
     */
    public function isValid($rootNodeId): bool;

    /**
     * Rebuild broken tree left indexes, right indexes, levels.
     *
     * @param int|string $rootNodeId
     */
    public function rebuild($rootNodeId): void;
}
