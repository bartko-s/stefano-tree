<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception\ValidationException;

interface MoveStrategyInterface
{
    /**
     * @param null|int|string $sourceNodeId
     * @param null|int|string $targetNodeId
     *
     * @throws ValidationException if was not moved
     */
    public function move($sourceNodeId, $targetNodeId): void;
}
