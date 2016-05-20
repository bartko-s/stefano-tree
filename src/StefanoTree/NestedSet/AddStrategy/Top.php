<?php
namespace StefanoTree\NestedSet\AddStrategy;

class Top
    extends AddStrategyAbstract
{
    public function canAddNewNode()
    {
        return ($this->getTargetNode()->isRoot()) ? false : true;
    }

    public function moveIndexesFromIndex()
    {
        return $this->getTargetNode()->getLeft() - 1;
    }

    public function newParentId()
    {
        return $this->getTargetNode()->getParentId();
    }

    public function newLevel()
    {
        return $this->getTargetNode()->getLevel();
    }

    public function newLeftIndex()
    {
        return $this->getTargetNode()->getLeft();
    }

    public function newRightIndex()
    {
        return $this->getTargetNode()->getLeft() + 1;
    }
}
