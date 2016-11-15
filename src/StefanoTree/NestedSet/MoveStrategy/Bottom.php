<?php
namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception;

class Bottom
    extends MoveStrategyAbstract
{
    public function getNewParentId()
    {
        return $this->getTargetNode()
                    ->getParentId();
    }

    public function getLevelShift()
    {
        return $this->getTargetNode()->getLevel() - $this->getSourceNode()->getLevel();
    }

    public function getHoleLeftIndex()
    {
        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getLeft() + $this->getIndexShift();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function getHoleRightIndex()
    {
        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function getSourceNodeIndexShift()
    {
        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            return $this->getTargetNode()->getRight() - $this->getSourceNode()->getLeft() + 1;
        } elseif ($this->isMovedUp()) {
            return $this->getTargetNode()->getRight() - $this->getSourceNode()->getLeft()
                + 1 - $this->getIndexShift();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function fixHoleFromIndex()
    {
        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getLeft() + $this->getIndexShift();
        } else {
            throw new Exception\TreeIsBrokenException();
        }
    }

    public function makeHoleFromIndex()
    {
        return $this->getTargetNode()->getRight();
    }

    public function canMoveBranch()
    {
        if (false == parent::canMoveBranch()) {
            return false;
        }

        return ($this->getTargetNode()->isRoot()) ? false : true;
    }

    public function isSourceNodeAtRequiredPosition()
    {
        $sourceNode = $this->getSourceNode();
        $targetNode = $this->getTargetNode();

        return ($targetNode->getRight() == ($sourceNode->getLeft() - 1) &&
                $targetNode->getParentId() == $sourceNode->getParentId()) ?
            true : false;
    }
}
