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
     * @return int|string Id of new created node
     *
     * @throws ValidationException
     */
    public function add($targetNodeId, array $data = array());
}
