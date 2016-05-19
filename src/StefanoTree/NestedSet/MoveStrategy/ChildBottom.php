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
        if ($this->isMovedToRoot()) {
            return $this->getSourceNode()->getLeft();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getLeft() + $this->getIndexShift();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getLeft();
        } else {
            // @codeCoverageIgnoreStart
            throw new Exception\BaseException('Cannot move node');
            // @codeCoverageIgnoreEnd
        }
    }

    public function getHoleRightIndex()
    {
        if ($this->isMovedToRoot()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight() + $this->getIndexShift();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getRight();
        } else {
            // @codeCoverageIgnoreStart
            throw new Exception\BaseException('Cannot move node');
            // @codeCoverageIgnoreEnd
        }
    }

    public function getSourceNodeIndexShift()
    {
        if ($this->isMovedToRoot()) {
            return $this->getTargetNode()->getRight() - $this->getSourceNode()->getLeft();
        } elseif ($this->isMovedUp()) {
            return $this->getTargetNode()->getRight() - $this->getSourceNode()->getRight() - 1;
        } elseif ($this->isMovedDown()) {
            return $this->getTargetNode()->getRight() - $this->getSourceNode()->getLeft();
        } else {
            // @codeCoverageIgnoreStart
            throw new Exception\BaseException('Cannot move node');
            // @codeCoverageIgnoreEnd
        }
    }

    public function fixHoleFromIndex()
    {
        if ($this->isMovedToRoot()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedUp()) {
            return $this->getSourceNode()->getRight();
        } elseif ($this->isMovedDown()) {
            return $this->getSourceNode()->getRight();
        } else {
            // @codeCoverageIgnoreStart
            throw new Exception\BaseException('Cannot move node');
            // @codeCoverageIgnoreEnd
        }
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
