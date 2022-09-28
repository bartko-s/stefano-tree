<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Validator;

use StefanoTree\Exception\ValidationException;

interface ValidatorInterface
{
    /**
     * Check if tree indexes, levels is not corrupted.
     *
     * @param int|string $rootNodeId
     *
     * @return bool
     *
     * @throws ValidationException if cannot validate tree
     */
    public function isValid($rootNodeId): bool;

    /**
     * Rebuild broken tree left indexes, right indexes, levels.
     *
     * @param int|string $rootNodeId
     *
     * @throws ValidationException if cannot rebuild tree
     */
    public function rebuild($rootNodeId): void;
}
