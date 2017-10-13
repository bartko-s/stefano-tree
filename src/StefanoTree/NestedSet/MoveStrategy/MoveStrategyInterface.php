<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception\ValidationException;

interface MoveStrategyInterface
{
    /**
     * @param int|string|null $sourceNodeId
     * @param int|string|null $targetNodeId
     *
     * @throws ValidationException if was not moved
     */
    public function move($sourceNodeId, $targetNodeId): void;
}
