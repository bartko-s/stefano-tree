<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyInterface;
use StefanoTree\Adapter\Helper\NodeInfo;

class Top
    implements AddStrategyInterface
{
    public function calculateNewNode(NodeInfo $targetNodeInfo) {
        $data = array(
            'id'        => null,
            'parentId'  => $targetNodeInfo->getParentId(),
            'level'     => $targetNodeInfo->getLevel(),
            'left'      => $targetNodeInfo->getLeft(),
            'right'     => $targetNodeInfo->getLeft() + 1,
        );
        
        return new NodeInfo($data);
    }

    public function moveIndexesFromIndex(NodeInfo $targetNodeInfo) {
        return $targetNodeInfo->getLeft() - 1;
    }
}
