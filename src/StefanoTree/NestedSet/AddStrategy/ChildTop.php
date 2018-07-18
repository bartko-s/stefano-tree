<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\NestedSet\NodeInfo;

class ChildTop extends AddStrategyAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function canCreateNewNode(NodeInfo $targetNode): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function makeHole(NodeInfo $targetNode): void
    {
        $moveFromIndex = $targetNode->getLeft();
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
            $targetNode->getId(),
            $targetNode->getLevel() + 1,
            $targetNode->getLeft() + 1,
            $targetNode->getLeft() + 2,
            $targetNode->getScope()
        );
    }
}
