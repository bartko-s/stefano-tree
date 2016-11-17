<?php
namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception;

class Top
    extends MoveStrategyAbstract
{
    public function getNewParentId()
    {
        return $this->getTargetNode()->getParentId();
    }

    public function getLevelShift()
    {
        return $this->getTargetNode()->getLevel() - $this->getSourceNode()->getLevel();
    }

    public function getHoleLeftIndex()
    {
        if ($this->isMovedToRoot() || $this->isMovedUp()) {
            return $this->getSourceNode()->getLeft() + $this->getIndexShift();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function getHoleRightIndex()
    {
        if ($this->isMovedToRoot() || $this->isMovedUp()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getRight();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function getSourceNodeIndexShift()
    {
        if ($this->isMovedToRoot() || $this->isMovedUp()) {
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getRight() - 1;
        } elseif ($this->isMovedDown()) {
            return $this->getTargetNode()->getLeft() - $this->getSourceNode()->getLeft();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function fixHoleFromIndex()
    {
        if ($this->isMovedToRoot()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function makeHoleFromIndex()
    {
        return $this->getTargetNode()->getLeft() - 1;
    }

    public function canMoveBranch()
    {
        if (false == parent::canMoveBranch()) {
            return false;
        }

        return ($this->getTargetNode()->isRoot()) ?
            false : true;
    }

    public function isSourceNodeAtRequiredPosition()
    {
        $sourceNode = $this->getSourceNode();
        $targetNode = $this->getTargetNode();

        return ($targetNode->getLeft() == ($sourceNode->getRight() + 1) &&
                $targetNode->getParentId() == $sourceNode->getParentId()) ?
            true : false;
    }
}
