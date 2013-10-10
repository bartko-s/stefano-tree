<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\Helper\NodeInfo;

interface AddStrategyInterface
{
    /**
     * @param NodeInfo $targetNodeInfo
     * @return NodeInfo
     */
    public function calculateNewNode(NodeInfo $targetNodeInfo);

    /**
     * @param NodeInfo $targetNodeInfo
     * @return int
     */
    public function moveIndexesFromIndex(NodeInfo $targetNodeInfo);
}