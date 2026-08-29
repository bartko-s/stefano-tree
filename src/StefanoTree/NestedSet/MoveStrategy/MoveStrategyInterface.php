<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception\ValidationException;

interface MoveStrategyInterface
{
    /**
     * @param int|string $sourceNodeId
     * @param int|string $targetNodeId
     *
     * @throws ValidationException if was not moved
     */
    public function move(int|string $sourceNodeId, int|string $targetNodeId): void;
}
