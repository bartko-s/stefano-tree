<?php
namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception;

class ChildBottom
    extends MoveStrategyAbstract
{
    public function getNewParentId()
    {
        return $this->getTargetNode()->getId();
    }

    public function getLevelShift()
    {
        return $this->getTargetNode()->getLevel() - $this->getSourceNode()->getLevel() + 1;
    }

    public function getHoleLeftIndex()
    {
        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getLeft() + $this->getIndexShift();
        } else {
            throw new Exception\BaseException('Cannot move node');
        }
    }

    public function getHoleRightIndex()
    {
        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } else {
            throw new Exception\BaseException('Cannot move node');
        }
    }

    public function getSourceNodeIndexShift()
    {
        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            return $this->getTargetNode()->getRight() - $this->getSourceNode()->getLeft();
        } elseif ($this->isMovedUp()) {
            return $this->getTargetNode()->getRight() - $this->getSourceNode()->getRight() - 1;
        } else {
            throw new Exception\BaseException('Cannot move node');
        }
    }

    public function fixHoleFromIndex()
    {
        return $this->getSourceNode()->getRight();
    }

    public function makeHoleFromIndex()
    {
        return $this->getTargetNode()->getRight() - 1;
    }

    public function isSourceNodeAtRequiredPosition()
    {
        $sourceNode = $this->getSourceNode();
        $targetNode = $this->getTargetNode();

        return ($sourceNode->getParentId() == $targetNode->getId() &&
                $sourceNode->getRight() == ($targetNode->getRight() - 1)) ?
            true : false;
    }
}
