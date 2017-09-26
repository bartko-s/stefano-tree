<?php

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\NodeInfo;

/**
 * Lock, Unlock db table
 * Begin, Commit, Rollback Db Transaction
 * Other methods which manipulate with trees.
 */
interface AdapterInterface
{
    /**
     * Lock tree for update. This prevent race condition issue.
     */
    public function lockTree();

    /**
     * Begin db transaction.
     */
    public function beginTransaction();

    /**
     * Commit db transaction.
     */
    public function commitTransaction();

    /**
     * Rollback db transaction.
     */
    public function rollbackTransaction();

    /**
     * Update node data. Function must sanitize data from keys like level, leftIndex, ...
     *
     * @param int   $nodeId
     * @param array $data
     */
    public function update($nodeId, array $data);

    /**
     * @param NodeInfo $nodeInfo
     * @param array    $data
     *
     * @return int Last ID
     */
    public function insert(NodeInfo $nodeInfo, array $data);

    /**
     * Delete branch.
     *
     * @param int $nodeId
     */
    public function delete($nodeId);

    /**
     * @param int             $fromIndex Left index is greater than
     * @param int             $shift
     * @param null|string|int $scope     null if scope is not used
     */
    public function moveLeftIndexes($fromIndex, $shift, $scope = null);

    /**
     * @param int             $fromIndex Right index is greater than
     * @param int             $shift
     * @param null|string|int $scope     null if scope is not used
     */
    public function moveRightIndexes($fromIndex, $shift, $scope = null);

    /**
     * @param int $nodeId
     * @param int $newParentId
     */
    public function updateParentId($nodeId, $newParentId);

    /**
     * @param int             $leftIndexFrom from left index or equal
     * @param int             $rightIndexTo  to right index or equal
     * @param int             $shift         shift
     * @param null|string|int $scope         null if scope is not used
     */
    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift, $scope = null);

    /**
     * @param int             $leftIndexFrom from left index
     * @param int             $rightIndexTo  to right index
     * @param int             $shift
     * @param null|string|int $scope         null if scope is not used
     */
    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift, $scope = null);

    /**
     * @param int $nodeId
     *
     * @return null|array
     */
    public function getNode($nodeId);

    /**
     * @param int $nodeId
     *
     * @return NodeInfo|null
     */
    public function getNodeInfo($nodeId);

    /**
     * Children must be find by parent ID column and order by left index !!!
     *
     * @param $parentNodeId int
     *
     * @return array
     */
    public function getChildrenNodeInfo($parentNodeId);

    /**
     * Update left index, right index, level. Other columns must be ignored.
     *
     * @param NodeInfo $nodeInfo
     */
    public function updateNodeMetadata(NodeInfo $nodeInfo);

    /**
     * @param int  $nodeId
     * @param int  $startLevel      0 = include root
     * @param bool $excludeLastNode
     *
     * @return array
     */
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false);

    /**
     * @param int      $nodeId
     * @param int      $startLevel    Relative level from $nodeId. 1 = exclude $nodeId from result.
     *                                2 = exclude 2 levels from result
     * @param null|int $levels        Number of levels in the results relative to $startLevel
     * @param null|int $excludeBranch Exclude defined branch(node id) from result
     *
     * @return array
     */
    public function getDescendants($nodeId, $startLevel = 0, $levels = null, $excludeBranch = null);

    /**
     * @param null|string|int $scope null if scope is not used
     *
     * @return array
     */
    public function getRoot($scope = null);

    /**
     * @param null|string|int $scope if defined return root only for defined scope
     *
     * @return array
     */
    public function getRoots($scope = null);
}
