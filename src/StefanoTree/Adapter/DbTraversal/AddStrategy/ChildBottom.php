<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyInterface;
use StefanoTree\Adapter\Helper\NodeInfo;

class ChildBottom
    implements AddStrategyInterface
{
    public function calculateNewNode(NodeInfo $targetNodeInfo) {
        $data = array(
            'id'        => null,
            'parentId'  => $targetNodeInfo->getId(),
            'level'     => $targetNodeInfo->getLevel() + 1,
            'left'      => $targetNodeInfo->getRight(),
            'right'     => $targetNodeInfo->getRight() + 1,
        );
        
        return new NodeInfo($data);
    }

    public function moveIndexesFromIndex(NodeInfo $targetNodeInfo) {
        return $targetNodeInfo->getRight() - 1;
    }
}
