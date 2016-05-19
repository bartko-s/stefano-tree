<?php
namespace StefanoTree\NestedSet\AddStrategy;

class ChildBottom
    extends AddStrategyAbstract
{
    public function moveIndexesFromIndex()
    {
        return $this->getTargetNode()->getRight() - 1;
    }

    public function newParentId()
    {
        return $this->getTargetNode()->getId();
    }

    public function newLevel()
    {
        return $this->getTargetNode()->getLevel() + 1;
    }

    public function newLeftIndex()
    {
        return $this->getTargetNode()->getRight();
    }

    public function newRightIndex()
    {
        return $this->getTargetNode()->getRight() + 1;
    }
}
