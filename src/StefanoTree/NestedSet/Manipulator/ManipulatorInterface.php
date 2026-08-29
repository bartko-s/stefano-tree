<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Manipulator;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;

interface ManipulatorInterface
{
    /**
     * @return Options
     */
    public function getOptions(): Options;

    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface;

    /**
     * Lock tree for update. This prevent race condition issue.
     */
    public function lockTree(): void;

    /**
     * Begin db transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commit db transaction.
     */
    public function commitTransaction(): void;

    /**
     * Rollback db transaction.
     */
    public function rollbackTransaction(): void;

    /**
     * Update node data. Function must sanitize data from keys like level, leftIndex, ...
     *
     * @param int|string           $nodeId
     * @param array<string, mixed> $data
     */
    public function update(int|string $nodeId, array $data): void;

    /**
     * @param NodeInfo             $nodeInfo
     * @param array<string, mixed> $data
     *
     * @return int|string Last ID
     */
    public function insert(NodeInfo $nodeInfo, array $data): int|string;

    /**
     * Delete branch.
     *
     * @param int|string $nodeId
     */
    public function delete(int|string $nodeId): void;

    /**
     * @param int             $fromIndex Left index is greater than
     * @param int             $shift
     * @param null|int|string $scope     null if scope is not used
     */
    public function moveLeftIndexes(int $fromIndex, int $shift, int|string|null $scope = null): void;

    /**
     * @param int             $fromIndex Right index is greater than
     * @param int             $shift
     * @param null|int|string $scope     null if scope is not used
     */
    public function moveRightIndexes(int $fromIndex, int $shift, int|string|null $scope = null): void;

    /**
     * @param int|string $nodeId
     * @param int|string $newParentId
     */
    public function updateParentId(int|string $nodeId, int|string $newParentId): void;

    /**
     * @param int             $leftIndexFrom from left index or equal
     * @param int             $rightIndexTo  to right index or equal
     * @param int             $shift         shift
     * @param null|int|string $scope         null if scope is not used
     */
    public function updateLevels(int $leftIndexFrom, int $rightIndexTo, int $shift, int|string|null $scope = null): void;

    /**
     * @param int             $leftIndexFrom from left index
     * @param int             $rightIndexTo  to right index
     * @param int             $shift
     * @param null|int|string $scope         null if scope is not used
     */
    public function moveBranch(int $leftIndexFrom, int $rightIndexTo, int $shift, int|string|null $scope = null): void;

    /**
     * @param int|string $nodeId
     *
     * @return null|array<string, mixed>
     */
    public function getNode(int|string $nodeId): ?array;

    /**
     * @param int|string $nodeId
     *
     * @return null|NodeInfo
     */
    public function getNodeInfo(int|string $nodeId): ?NodeInfo;

    /**
     * Children must be find by parent ID column and order by left index !!!
     *
     * @param int|string $parentNodeId
     *
     * @return NodeInfo[]
     */
    public function getChildrenNodeInfo(int|string $parentNodeId): array;

    /**
     * Update left index, right index, level. Other columns must be ignored.
     *
     * @param NodeInfo $nodeInfo
     */
    public function updateNodeMetadata(NodeInfo $nodeInfo): void;

    /**
     * @param int|string $nodeId
     * @param int        $startLevel         0 = include root
     * @param int        $excludeLastNLevels
     *
     * @return list<array<string, mixed>>
     */
    public function getAncestors(int|string $nodeId, int $startLevel = 0, int $excludeLastNLevels = 0): array;

    /**
     * @param int|string      $nodeId
     * @param int             $startLevel    Relative level from $nodeId. 1 = exclude $nodeId from result.
     *                                       2 = exclude 2 levels from result
     * @param null|int        $levels        Number of levels in the results relative to $startLevel
     * @param null|int|string $excludeBranch Exclude defined branch(node id) from result
     *
     * @return list<array<string, mixed>>
     */
    public function getDescendants(int|string $nodeId, int $startLevel = 0, ?int $levels = null, int|string|null $excludeBranch = null): array;

    /**
     * @param null|int|string $scope null if scope is not used
     *
     * @return array<string, mixed>
     */
    public function getRoot(int|string|null $scope = null): array;

    /**
     * @param null|int|string $scope if defined return root only for defined scope
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoots(int|string|null $scope = null): array;
}
