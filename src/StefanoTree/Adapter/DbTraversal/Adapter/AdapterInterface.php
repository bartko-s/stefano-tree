<?php
namespace StefanoTree\Adapter\DbTraversal\Adapter;

use StefanoTree\Adapter\DbTraversal\NodeInfo;

/**
 * Lock, Unlock db table
 * Begin, Commit, Roolback Db Transaction (must support nested transaction)
 * Other methods which manipulate with trees
 */
interface AdapterInterface
{
    /**
     * Lock tree table
     * @return this
     */
    public function lockTable();

    /**
     * Unlock tree table
     * @return this
     */
    public function unlockTable();

    /**
     * Begin db transation only if transaction has not been started before
     * @return this
     */
    public function beginTransaction();

    /**
     * Commit db transaction. Only if transaction start this class
     * @return this
     */
    public function commitTransaction();

    /**
     * Rollback db transaction
     * @return this
     */
    public function rollbackTransaction();

    /**
     * Update node data. Functin must sanitize data from keys like level, leftIndex, ...
     *
     * @param int $nodeId
     * @param array $data
     * @param NodeInfo $nodeInfo
     * @return this
     */
    public function update($nodeId, array $data, NodeInfo $nodeInfo = null);

    /**
     * @param NodeInfo $nodeInfo
     * @param array $data
     * @return int Last ID
     */
    public function insert(NodeInfo $nodeInfo, array $data);

    /**
     * Delete branch
     *
     * @param int $leftIndex Left index greater or eqoul to
     * @param int $rightIndex Right index greater or eqoul to
     * @return this
     */
    public function delete($leftIndex, $rightIndex);

    /**
     * @param int $expectNodeId Delete all expect this node
     * @return this
     */
    public function deleteAll($expectNodeId);

    /**
     * @param int $fromIndex Left index is greater than
     * @shift int $shift
     * @return this
     */
    public function moveLeftIndexes($fromIndex, $shift);

    /**
     * @param int $fromIndex Right index is greater than
     * @shift int $shift
     * @return this
     */
    public function moveRightIndexes($fromIndex, $shift);

    /**
     * @param int $nodeId
     * @param int $newParentId
     * @return this
     */
    public function updateParentId($nodeId, $newParentId);

    /**
     * @param int $leftIndexFrom from left index or equal
     * @param int $rightIndexTo to right index or equal
     * @param int $shift shift
     */
    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift);

    /**
     * @param int $leftIndexFrom from left index
     * @param int $rightIndexTo to right index
     * @param int $shift
     */
    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift);

    /**
     * @param int $nodeId
     * @return null|array
     */
    public function getNode($nodeId);

    /**
     * @param int $id
     * @return NodeInfo|null
     */
    public function getNodeInfo($nodeId);

    /**
     * @param int $nodeId
     * @param int $startLevel 0 = vratane root
     * @param bolean $excludeLastNode
     * @return null|array
     */
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false);

    /**
     * @param int $nodeId
     * @param int $startLevel Relative level from $nodeId. 1 = excelude $nodeId from result.
     *                        2 = excelude 2 levels from result
     * @param int $levels Number of levels in the restults relative to $startLevel
     * @param int $excludeBranche Exclude defined branche(node id) from result
     * @return null|array
     */
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranche = null);
}
