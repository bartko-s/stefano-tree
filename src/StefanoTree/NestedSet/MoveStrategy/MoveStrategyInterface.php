<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

interface MoveStrategyInterface
{
    /**
     * @param int|string|null $sourceNodeId
     * @param int|string|null $targetNodeId
     *
     * @return bool
     */
    public function move($sourceNodeId, $targetNodeId): bool;
}
