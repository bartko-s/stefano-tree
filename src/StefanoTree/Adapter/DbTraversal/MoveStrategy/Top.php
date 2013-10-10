<?php
namespace StefanoTree\Adapter\DbTraversal\MoveStrategy;

use StefanoTree\Adapter\DbTraversal\MoveStrategy\MoveStrategyAbstract;

class Top
    extends MoveStrategyAbstract
{
    public function getNewParentId() {
        return $this->getTargetNode()->getParentId();
    }

    public function getLevelShift() {
        return $this->getTargetNode()->getLevel() - $this->getSourceNode()->getLevel();
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
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getRight() - 1;
        } elseif ($this->isMovedUp()) {
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getRight() - 1;
        } elseif ($this->isMovedDown()) {
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getLeft();
        } else {
            throw \Exception('Cannot move node');
        }
    }

    public function fixHoleFromIndex() {
        if($this->isMovedToRoot()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } else {
            throw \Exception('Cannot move node');
        }
    }

    public function makeHoleFromIndex() {
        return $this->getTargetNode()->getLeft() - 1;
    }
}
