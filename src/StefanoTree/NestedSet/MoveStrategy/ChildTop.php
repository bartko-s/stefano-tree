<?php
namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\NestedSet\MoveStrategy\MoveStrategyAbstract;

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
            // @codeCoverageIgnoreStart
            throw \Exception('Cannot move node');
            // @codeCoverageIgnoreEnd
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
            // @codeCoverageIgnoreStart
            throw \Exception('Cannot move node');
            // @codeCoverageIgnoreEnd
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
            // @codeCoverageIgnoreStart
            throw \Exception('Cannot move node');
            // @codeCoverageIgnoreEnd
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
            // @codeCoverageIgnoreStart
            throw \Exception('Cannot move node');
            // @codeCoverageIgnoreEnd
        }
    }

    public function makeHoleFromIndex() {
        return $this->getTargetNode()->getLeft();
    }

    public function isSourceNodeAtRequiredPosition() {
        $sourceNode = $this->getSourceNode();
        $targetNode = $this->getTargetNode();

        return ($sourceNode->getParentId() == $targetNode->getId() &&
                $targetNode->getLeft() == ($sourceNode->getLeft() - 1)) ?
            true : false;
    }
}
