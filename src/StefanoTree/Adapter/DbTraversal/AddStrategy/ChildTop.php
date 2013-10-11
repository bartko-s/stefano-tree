<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyAbstract;
use StefanoTree\Adapter\Helper\NodeInfo;

class ChildTop
    extends AddStrategyAbstract
{
    public function calculateNewNode() {
        $data = array(
            'id'        => null,
            'parentId'  => $this->getTargetNode()->getId(),
            'level'     => $this->getTargetNode()->getLevel() + 1,
            'left'      => $this->getTargetNode()->getLeft() + 1,
            'right'     => $this->getTargetNode()->getLeft() + 2,
        );
        
        return new NodeInfo($data);
    }

    public function moveIndexesFromIndex() {
        return $this->getTargetNode()->getLeft();
    }
}
