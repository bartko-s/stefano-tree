<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet\NodeInfo;

class Bottom extends AddStrategyAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function canCreateNewNode(NodeInfo $targetNode): void
    {
        if ($targetNode->isRoot()) {
            throw new ValidationException('Cannot create node. Target node is root. Root node cannot have sibling.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function makeHole(NodeInfo $targetNode): void
    {
        $moveFromIndex = $targetNode->getRight();
        $this->getAdapter()->moveLeftIndexes($moveFromIndex, 2, $targetNode->getScope());
        $this->getAdapter()->moveRightIndexes($moveFromIndex, 2, $targetNode->getScope());
    }

    /**
     * {@inheritdoc}
     */
    protected function createNewNodeNodeInfo(NodeInfo $targetNode): NodeInfo
    {
        return new NodeInfo(
            null,
            $targetNode->getParentId(),
            $targetNode->getLevel(),
            $targetNode->getRight() + 1,
            $targetNode->getRight() + 2,
            $targetNode->getScope()
        );
    }
}
