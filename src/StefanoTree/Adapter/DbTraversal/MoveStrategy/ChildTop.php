<?php
namespace StefanoTree\Adapter\DbTraversal\MoveStrategy;

use StefanoTree\Adapter\DbTraversal\MoveStrategy\MoveStrategyAbstract;

class ChildTop
    extends MoveStrategyAbstract
{
    public function getNewParentId() {
        return $this->getTargetNode()->getId();
    }

    public function getLevelShift() {
        return $this->getTargetNode()->getLevel() - $this->getSourceNode()->getLevel() + 1;
    }

    public function getHoleLeftIndex() {
        if($this->isMovedToRoot()) {
            return $this->getSourceNode()->getLeft() + $this->getIndexShift();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getLeft() + $this->getIndexShift();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } else {
            throw \Exception('Cannot move node');
        }
    }

    public function getHoleRightIndex() {
        if($this->isMovedToRoot()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getRight();
        } else {
            throw \Exception('Cannot move node');
        }
    }

    public function getSourceNodeIndexShift() {
        if($this->isMovedToRoot()) {
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getRight();
        } elseif ($this->isMovedUp()) {
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getRight();
        } elseif ($this->isMovedDown()) {
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getLeft() + 1;
        } else {
            throw \Exception('Cannot move node');
        }
    }

    public function fixHoleFromIndex() {
        if($this->isMovedToRoot()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getRight();
        } else {
            throw \Exception('Cannot move node');
        }
    }

    public function makeHoleFromIndex() {
        return $this->getTargetNode()->getLeft();
    }
}
