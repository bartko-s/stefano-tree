<?php
namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\NodeInfo;

/**
 * Lock, Unlock db table
 * Begin, Commit, Rollback Db Transaction (must support nested transaction)
 * Other methods which manipulate with trees
 */
interface AdapterInterface
{
    /**
     * Lock tree for update. This prevent race condition issue
     *
     * @param $scope int null if scope is not used
     * @return void
     */
    public function lockTree($scope);

    /**
     * Begin db transaction only if transaction has not been started before
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit db transaction. Only if transaction start this class
     * @return void
     */
    public function commitTransaction();

    /**
     * Rollback db transaction
     * @return void
     */
    public function rollbackTransaction();

    /**
     * Update node data. Function must sanitize data from keys like level, leftIndex, ...
     *
     * @param int $nodeId
     * @param array $data
     * @return void
     */
    public function update($nodeId, array $data);

    /**
     * @param NodeInfo $nodeInfo
     * @param array $data
     * @return int Last ID
     */
    public function insert(NodeInfo $nodeInfo, array $data);

    /**
     * Delete branch
     *
     * @param int $leftIndex Left index greater or equal to
     * @param int $rightIndex Right index greater or equal to
     * @param int $scope null if scope is not used
     * @return void
     */
    public function delete($leftIndex, $rightIndex, $scope = null);

    /**
     * @param int $fromIndex Left index is greater than
     * @param int $shift
     * @param int $scope null if scope is not used
     * @return void
     */
    public function moveLeftIndexes($fromIndex, $shift, $scope = null);

    /**
     * @param int $fromIndex Right index is greater than
     * @param int $shift
     * @param int $scope null if scope is not used
     * @return void
     */
    public function moveRightIndexes($fromIndex, $shift, $scope = null);

    /**
     * @param int $nodeId
     * @param int $newParentId
     * @return void
     */
    public function updateParentId($nodeId, $newParentId);

    /**
     * @param int $leftIndexFrom from left index or equal
     * @param int $rightIndexTo to right index or equal
     * @param int $shift shift
     * @param int $scope null if scope is not used
     * @return void
     */
    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift, $scope = null);

    /**
     * @param int $leftIndexFrom from left index
     * @param int $rightIndexTo to right index
     * @param int $shift
     * @param int $scope null if scope is not used 
     * @return void
     */
    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift, $scope = null);

    /**
     * @param int $nodeId
     * @return null|array
     */
    public function getNode($nodeId);

    /**
     * @param int $nodeId
     * @return NodeInfo|null
     */
    public function getNodeInfo($nodeId);

    /**
     * Children must be find by parent ID column and order by left index !!!
     *
     * @param $parentNodeId int
     * @return array
     */
    public function getChildrenNodeInfo($parentNodeId);

    /**
     * Update left index, right index, level. Other columns must be ignored.
     * 
     * @param NodeInfo $nodeInfo
     * @return void
     */
    public function updateNodeMetadata(NodeInfo $nodeInfo);

    /**
     * @param int $nodeId
     * @param int $startLevel 0 = include root
     * @param boolean $excludeLastNode
     * @return array
     */
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false);

    /**
     * @param int $nodeId
     * @param int $startLevel Relative level from $nodeId. 1 = exclude $nodeId from result.
     *                        2 = exclude 2 levels from result
     * @param int $levels Number of levels in the results relative to $startLevel
     * @param int $excludeBranch Exclude defined branch(node id) from result
     * @return array
     */
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null);

    /**
     * @param int $scope null if scope is not used
     * @return array
     */
    public function getRoot($scope = null);

    /**
     * @param $scope int if defined return root only for defined scope
     * @return array
     */
    public function getRoots($scope = null);
}
