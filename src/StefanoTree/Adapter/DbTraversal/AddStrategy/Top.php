<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyAbstract;
use StefanoTree\Adapter\Helper\NodeInfo;

class Top
    extends AddStrategyAbstract
{
    public function calculateNewNode() {
        $data = array(
            'id'        => null,
            'parentId'  => $this->getTargetNode()->getParentId(),
            'level'     => $this->getTargetNode()->getLevel(),
            'left'      => $this->getTargetNode()->getLeft(),
            'right'     => $this->getTargetNode()->getLeft() + 1,
        );
        
        return new NodeInfo($data);
    }

    public function moveIndexesFromIndex() {
        return $this->getTargetNode()->getLeft() - 1;
    }
}
