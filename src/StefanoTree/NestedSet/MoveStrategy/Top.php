<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception\TreeIsBrokenException;

class Top extends MoveStrategyAbstract implements MoveStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    protected function canMoveBranch(): bool
    {
        $isTargetInsideSource = $this->isTargetNodeInsideSourceBranch();

        return ($this->getTargetNodeInfo()->isRoot() || $isTargetInsideSource) ? false : true;
    }

    /**
     * {@inheritdoc}
     */
    protected function isSourceNodeAtRequiredPosition(): bool
    {
        $source = $this->getSourceNodeInfo();
        $target = $this->getTargetNodeInfo();

        return ($target->getLeft() == ($source->getRight() + 1) && $target->getParentId() == $source->getParentId()) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateParentId(): void
    {
        $newParentId = $this->getTargetNodeInfo()->getParentId();
        $this->_updateParentId($this->getSourceNodeInfo(), $newParentId);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateLevels(): void
    {
        $source = $this->getSourceNodeInfo();

        $levelShift = $this->getTargetNodeInfo()->getLevel() - $source->getLevel();
        $this->_updateLevels($source, $levelShift);
    }

    /**
     * {@inheritdoc}
     */
    protected function makeHole(): void
    {
        $holeFromIndex = $this->getTargetNodeInfo()->getLeft() - 1;
        $indexShift = $this->getIndexShift();
        $scope = $this->getSourceNodeInfo()->getScope();

        $this->_makeHole($holeFromIndex, $indexShift, $scope);
    }

    /**
     * {@inheritdoc}
     */
    protected function moveBranchToTheHole(): void
    {
        $source = $this->getSourceNodeInfo();
        $target = $this->getTargetNodeInfo();

        if ($this->isMovedToRoot() || $this->isMovedUp()) {
            $leftIndex = $source->getLeft() + $this->getIndexShift();
            $rightIndex = $source->getRight() + $this->getIndexShift();
            $indexShift = $target->getLeft() - $source->getRight() - 1;
        } elseif ($this->isMovedDown()) {
            $leftIndex = $source->getLeft();
            $rightIndex = $source->getRight();
            $indexShift = $target->getLeft() - $source->getLeft();
        } else {
            throw new TreeIsBrokenException();
        }

        $scope = $source->getScope();

        $this->_moveBranchToTheHole($leftIndex, $rightIndex, $indexShift, $scope);
    }

    /**
     * {@inheritdoc}
     */
    protected function patchHole(): void
    {
        $source = $this->getSourceNodeInfo();

        if ($this->isMovedToRoot()) {
            $fromIndex = $source->getRight() + $this->getIndexShift();
        } elseif ($this->isMovedUp()) {
            $fromIndex = $source->getRight();
        } elseif ($this->isMovedDown()) {
            $fromIndex = $source->getLeft();
        } else {
            throw new TreeIsBrokenException();
        }

        $indexShift = $this->getIndexShift() * -1;
        $scope = $source->getScope();

        $this->_patchHole($fromIndex, $indexShift, $scope);
    }
}
