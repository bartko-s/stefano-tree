<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyAbstract;

class ChildTop
    extends AddStrategyAbstract
{
    public function moveIndexesFromIndex() {
        return $this->getTargetNode()->getLeft();
    }

    public function newParentId() {
        return $this->getTargetNode()->getId();
    }

    public function newLevel() {
        return $this->getTargetNode()->getLevel() + 1;
    }

    public function newLeftIndex() {
        return $this->getTargetNode()->getLeft() + 1;
    }

    public function newRightIndex() {
        return $this->getTargetNode()->getLeft() + 2;
    }
}
