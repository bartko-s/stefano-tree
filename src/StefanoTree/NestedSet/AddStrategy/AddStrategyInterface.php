<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

interface AddStrategyInterface
{
    /**
     * @param int|string $targetNodeId
     * @param array      $data
     *
     * @return int|string|null Id of new created node or null if node was not created
     */
    public function add($targetNodeId, array $data = array());
}
