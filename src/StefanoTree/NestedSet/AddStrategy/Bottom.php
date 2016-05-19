<?php
namespace StefanoTree\NestedSet\AddStrategy;

class Bottom
    extends AddStrategyAbstract
{
    public function canAddNewNode($rootNodeId)
    {
        return ($rootNodeId == $this->getTargetNode()->getId()) ? false : true;
    }

    public function moveIndexesFromIndex()
    {
        return $this->getTargetNode()->getRight();
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
        return $this->getTargetNode()->getRight() + 1;
    }

    public function newRightIndex()
    {
        return $this->getTargetNode()->getRight() + 2;
    }
}
