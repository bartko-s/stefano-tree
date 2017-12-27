<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception\TreeIsBrokenException;
use StefanoTree\Exception\ValidationException;

class Bottom extends MoveStrategyAbstract implements MoveStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    protected function canMoveBranch(): void
    {
        if ($this->isTargetNodeInsideSourceBranch()) {
            throw new ValidationException('Cannot move. Target node is inside source branch.');
        }

        if ($this->getTargetNodeInfo()->isRoot()) {
            throw new ValidationException('Cannot move. Target node is root. Root node cannot have sibling.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isSourceNodeAtRequiredPosition(): bool
    {
        $source = $this->getSourceNodeInfo();
        $target = $this->getTargetNodeInfo();

        return ($target->getRight() == ($source->getLeft() - 1) && $target->getParentId() == $source->getParentId()) ? true : false;
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
        $holeFromIndex = $this->getTargetNodeInfo()->getRight();
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

        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            $leftIndex = $source->getLeft();
            $rightIndex = $source->getRight();
            $indexShift = $target->getRight() - $source->getLeft() + 1;
        } elseif ($this->isMovedUp()) {
            $leftIndex = $source->getLeft() + $this->getIndexShift();
            $rightIndex = $source->getRight() + $this->getIndexShift();
            $indexShift = $target->getRight() - $source->getLeft() + 1 - $this->getIndexShift();
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

        if ($this->isMovedToRoot() || $this->isMovedDown()) {
            $fromIndex = $source->getLeft();
        } elseif ($this->isMovedUp()) {
            $fromIndex = $source->getLeft() + $this->getIndexShift();
        } else {
            throw new TreeIsBrokenException();
        }

        $indexShift = $this->getIndexShift() * -1;
        $scope = $source->getScope();

        $this->_patchHole($fromIndex, $indexShift, $scope);
    }
}
