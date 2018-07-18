<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet\NodeInfo;

class Top extends AddStrategyAbstract
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
        $moveFromIndex = $targetNode->getLeft() - 1;
        $this->getManipulator()->moveLeftIndexes($moveFromIndex, 2, $targetNode->getScope());
        $this->getManipulator()->moveRightIndexes($moveFromIndex, 2, $targetNode->getScope());
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
            $targetNode->getLeft(),
            $targetNode->getLeft() + 1,
            $targetNode->getScope()
        );
    }
}
