<?php

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\NestedSet\NodeInfo;

abstract class AddStrategyAbstract implements AddStrategyInterface
{
    protected $targetNode;

    /**
     * @param NodeInfo $targetNode
     */
    public function __construct(NodeInfo $targetNode)
    {
        $this->targetNode = $targetNode;
    }

    /**
     * @return NodeInfo
     */
    protected function getTargetNode()
    {
        return $this->targetNode;
    }

    public function canAddNewNode()
    {
        return true;
    }
}
