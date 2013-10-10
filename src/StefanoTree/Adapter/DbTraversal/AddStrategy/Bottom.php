<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyInterface;
use StefanoTree\Adapter\Helper\NodeInfo;

class Bottom
    implements AddStrategyInterface
{
    public function calculateNewNode(NodeInfo $targetNodeInfo) {
        $data = array(
            'id'        => null,
            'parentId'  => $targetNodeInfo->getParentId(),
            'level'     => $targetNodeInfo->getLevel(),
            'left'      => $targetNodeInfo->getRight() + 1,
            'right'     => $targetNodeInfo->getRight() + 2,
        );
        
        return new NodeInfo($data);
    }

    public function moveIndexesFromIndex(NodeInfo $targetNodeInfo) {
        return $targetNodeInfo->getRight();
    }
}
