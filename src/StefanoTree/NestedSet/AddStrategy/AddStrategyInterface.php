<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\Exception\ValidationException;

interface AddStrategyInterface
{
    /**
     * @param int|string           $targetNodeId
     * @param array<string, mixed> $data
     *
     * @return int|string Id of new created node
     *
     * @throws ValidationException
     */
    public function add(int|string $targetNodeId, array $data = array()): int|string;
}
