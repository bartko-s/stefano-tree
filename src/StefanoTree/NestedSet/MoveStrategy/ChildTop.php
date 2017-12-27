<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception\TreeIsBrokenException;
use StefanoTree\Exception\ValidationException;

class ChildTop extends MoveStrategyAbstract implements MoveStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    protected function canMoveBranch(): void
    {
        if ($this->isTargetNodeInsideSourceBranch()) {
            throw new ValidationException('Cannot move. Target node is inside source branch.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isSourceNodeAtRequiredPosition(): bool
    {
        $source = $this->getSourceNodeInfo();
        $target = $this->getTargetNodeInfo();

        return ($source->getParentId() == $target->getId() && $target->getLeft() == ($source->getLeft() - 1)) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateParentId(): void
    {
        $newParentId = $this->getTargetNodeInfo()->getId();
        $this->_updateParentId($this->getSourceNodeInfo(), $newParentId);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateLevels(): void
    {
        $source = $this->getSourceNodeInfo();

        $levelShift = $this->getTargetNodeInfo()->getLevel() - $source->getLevel() + 1;
        $this->_updateLevels($source, $levelShift);
    }

    /**
     * {@inheritdoc}
     */
    protected function makeHole(): void
    {
        $holeFromIndex = $this->getTargetNodeInfo()->getLeft();
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
            $indexShift = $target->getLeft() - $source->getRight();
        } elseif ($this->isMovedDown()) {
            $leftIndex = $source->getLeft();
            $rightIndex = $source->getRight();
            $indexShift = $target->getLeft() - $source->getLeft() + 1;
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

        $fromIndex = $source->getRight();
        $indexShift = $this->getIndexShift() * -1;
        $scope = $source->getScope();

        $this->_patchHole($fromIndex, $indexShift, $scope);
    }
}
