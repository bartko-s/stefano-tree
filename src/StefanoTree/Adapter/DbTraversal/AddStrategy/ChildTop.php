<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyInterface;
use StefanoTree\Adapter\Helper\NodeInfo;

class ChildTop
    implements AddStrategyInterface
{
    public function calculateNewNode(NodeInfo $targetNodeInfo) {
        $data = array(
            'id'        => null,
            'parentId'  => $targetNodeInfo->getId(),
            'level'     => $targetNodeInfo->getLevel() + 1,
            'left'      => $targetNodeInfo->getLeft() + 1,
            'right'     => $targetNodeInfo->getLeft() + 2,
        );
        
        return new NodeInfo($data);
    }

    public function moveIndexesFromIndex(NodeInfo $targetNodeInfo) {
        return $targetNodeInfo->getLeft();
    }
}
