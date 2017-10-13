<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\NestedSet\NodeInfo;

class ChildBottom extends AddStrategyAbstract
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
        $moveFromIndex = $targetNode->getRight() - 1;
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
            $targetNode->getId(),
            $targetNode->getLevel() + 1,
            $targetNode->getRight(),
            $targetNode->getRight() + 1,
            $targetNode->getScope()
        );
    }
}
