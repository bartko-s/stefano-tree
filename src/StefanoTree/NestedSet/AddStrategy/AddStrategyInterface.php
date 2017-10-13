<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\Exception\ValidationException;

interface AddStrategyInterface
{
    /**
     * @param int|string $targetNodeId
     * @param array      $data
     *
     * @throws ValidationException
     *
     * @return int|string Id of new created node
     */
    public function add($targetNodeId, array $data = array());
}
