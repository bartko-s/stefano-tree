<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;

abstract class MoveStrategyAbstract implements MoveStrategyInterface
{
    private $adapter;

    private $sourceNodeInfo;
    private $targetNodeInfo;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function move($sourceNodeId, $targetNodeId): void
    {
        $adapter = $this->getAdapter();

        if ($sourceNodeId == $targetNodeId) {
            throw new ValidationException('Cannot move. Source node and Target node are equal.');
        }

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $sourceNodeInfo = $adapter->getNodeInfo($sourceNodeId);
            $targetNodeInfo = $adapter->getNodeInfo($targetNodeId);

            if (!$sourceNodeInfo) {
                throw new ValidationException('Cannot move. Source node does not exists.');
            }

            if (!$targetNodeInfo) {
                throw new ValidationException('Cannot move. Target node does not exists.');
            }

            $this->setSourceNodeInfo($sourceNodeInfo);
            $this->setTargetNodeInfo($targetNodeInfo);

            if ($sourceNodeInfo->getScope() != $targetNodeInfo->getScope()) {
                throw new ValidationException('Cannot move node between scopes.');
            }

            $this->canMoveBranch();

            if ($this->isSourceNodeAtRequiredPosition()) {
                $adapter->commitTransaction();

                return;
            }

            $this->updateParentId();
            $this->updateLevels();
            $this->makeHole();
            $this->moveBranchToTheHole();
            $this->patchHole();

            $adapter->commitTransaction();
        } catch (\Exception $e) {
            $adapter->rollbackTransaction();

            throw $e;
        }
    }

    /**
     * Check if can move node.
     *
     * @throws ValidationException if cannot move branch
     */
    abstract protected function canMoveBranch(): void;

    /**
     * @return bool
     */
    abstract protected function isSourceNodeAtRequiredPosition(): bool;

    /**
     * @param NodeInfo        $sourceNodeInfo
     * @param string|int|null $newParentId
     */
    protected function _updateParentId(NodeInfo $sourceNodeInfo, $newParentId): void
    {
        if ($sourceNodeInfo->getParentId() != $newParentId) {
            $this->getAdapter()->updateParentId($sourceNodeInfo->getId(), $newParentId);
        }
    }

    /**
     * Update parent id.
     */
    abstract protected function updateParentId(): void;

    /**
     * @param NodeInfo $sourceNodeInfo
     * @param int      $levelShift
     */
    protected function _updateLevels(NodeInfo $sourceNodeInfo, int $levelShift): void
    {
        if (0 !== $levelShift) {
            $this->getAdapter()
                 ->updateLevels(
                    $sourceNodeInfo->getLeft(),
                    $sourceNodeInfo->getRight(),
                    $levelShift,
                    $sourceNodeInfo->getScope()
            );
        }
    }

    /**
     * Update levels.
     */
    abstract protected function updateLevels(): void;

    /**
     * @param int             $holeFromIndex
     * @param int             $indexShift
     * @param string|int|null $scope
     */
    protected function _makeHole(int $holeFromIndex, int $indexShift, $scope): void
    {
        $this->getAdapter()->moveLeftIndexes($holeFromIndex, $indexShift, $scope);
        $this->getAdapter()->moveRightIndexes($holeFromIndex, $indexShift, $scope);
    }

    /**
     * Make hole for moved branch.
     */
    abstract protected function makeHole(): void;

    /**
     * @param int             $leftIndex
     * @param int             $rightIndex
     * @param int             $indexShift
     * @param string|int|null $scope
     */
    protected function _moveBranchToTheHole(int $leftIndex, int $rightIndex, int $indexShift, $scope): void
    {
        $this->getAdapter()
             ->moveBranch($leftIndex, $rightIndex, $indexShift, $scope);
    }

    /**
     * Move branch to the Hole.
     */
    abstract protected function moveBranchToTheHole(): void;

    /**
     * @param int             $holeFromIndex
     * @param int             $indexShift
     * @param string|int|null $scope
     */
    protected function _patchHole(int $holeFromIndex, int $indexShift, $scope): void
    {
        $this->getAdapter()
             ->moveLeftIndexes($holeFromIndex, $indexShift, $scope);

        $this->getAdapter()
             ->moveRightIndexes($holeFromIndex, $indexShift, $scope);
    }

    /**
     * Patch hole.
     */
    abstract protected function patchHole(): void;

    /**
     * @return int
     */
    protected function getIndexShift(): int
    {
        $source = $this->getSourceNodeInfo();

        return $source->getRight() - $source->getLeft() + 1;
    }

    /**
     * @return bool
     */
    protected function isMovedUp(): bool
    {
        return ($this->getTargetNodeInfo()->getRight() < $this->getSourceNodeInfo()->getLeft()) ? true : false;
    }

    /**
     * @return bool
     */
    protected function isMovedDown(): bool
    {
        return ($this->getSourceNodeInfo()->getRight() < $this->getTargetNodeInfo()->getLeft()) ? true : false;
    }

    /**
     * @return bool
     */
    protected function isMovedToRoot(): bool
    {
        $source = $this->getSourceNodeInfo();
        $target = $this->getTargetNodeInfo();

        return ($source->getLeft() > $target->getLeft() && $source->getRight() < $target->getRight()) ? true : false;
    }

    /**
     * @return bool
     */
    protected function isTargetNodeInsideSourceBranch(): bool
    {
        $source = $this->getSourceNodeInfo();
        $target = $this->getTargetNodeInfo();

        return ($target->getLeft() > $source->getLeft() && $target->getRight() < $source->getRight()) ? true : false;
    }

    /**
     * @return AdapterInterface
     */
    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @return NodeInfo
     */
    protected function getSourceNodeInfo(): NodeInfo
    {
        return $this->sourceNodeInfo;
    }

    /**
     * @param NodeInfo $sourceNodeInfo
     */
    private function setSourceNodeInfo(NodeInfo $sourceNodeInfo): void
    {
        $this->sourceNodeInfo = $sourceNodeInfo;
    }

    /**
     * @return NodeInfo
     */
    protected function getTargetNodeInfo(): NodeInfo
    {
        return $this->targetNodeInfo;
    }

    /**
     * @param NodeInfo $targetNodeInfo
     */
    private function setTargetNodeInfo(NodeInfo $targetNodeInfo): void
    {
        $this->targetNodeInfo = $targetNodeInfo;
    }
}
