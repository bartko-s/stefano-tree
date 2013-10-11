<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyAbstract;
use StefanoTree\Adapter\Helper\NodeInfo;

class Bottom
    extends AddStrategyAbstract
{
    public function calculateNewNode() {
        $data = array(
            'id'        => null,
            'parentId'  => $this->getTargetNode()->getParentId(),
            'level'     => $this->getTargetNode()->getLevel(),
            'left'      => $this->getTargetNode()->getRight() + 1,
            'right'     => $this->getTargetNode()->getRight() + 2,
        );
        
        return new NodeInfo($data);
    }

    public function moveIndexesFromIndex() {
        return $this->getTargetNode()->getRight();
    }
}
