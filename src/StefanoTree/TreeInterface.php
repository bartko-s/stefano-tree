<?php
namespace StefanoTree;

interface TreeInterface
{   
    const PLACEMENT_TOP = 'top';
    const PLACEMENT_BOTTOM = 'bottom';
    const PLACEMENT_CHILD_TOP = 'childTop';
    const PLACEMENT_CHILD_BOTTOM = 'childBottom';
    
    /**
     * @param int $nodeId
     * @param array $data
     */
    public function updateNode($nodeId, $data);
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false Id of new created node. False if node has not been created
     */
    public function addNodePlacementBottom($targetNodeId, $data = array());
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false Id of new created node. False if node has not been created
     */
    public function addNodePlacementTop($targetNodeId, $data = array());
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false Id of new created node. False if node has not been created
     */
    public function addNodePlacementChildBottom($targetNodeId, $data = array());
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false Id of new created node. False if node has not been created
     */
    public function addNodePlacementChildTop($targetNodeId, $data = array());
    
    /**
     * @param int $sourceNodeId
     * @param int $targetNodeId
     * @return boolean
     */
    public function moveNodePlacementBottom($sourceNodeId, $targetNodeId);

    /**
     * @param int $sourceNodeId
     * @param int $targetNodeId
     * @return boolean
     */
    public function moveNodePlacementTop($sourceNodeId, $targetNodeId);
    
    /**
     * @param int $sourceNodeId
     * @param int $targetNodeId
     * @return boolean
     */
    public function moveNodePlacementChildBottom($sourceNodeId, $targetNodeId);
    
    /**
     * @param int $sourceNodeId
     * @param int $targetNodeId
     * @return boolean
     */
    public function moveNodePlacementChildTop($sourceNodeId, $targetNodeId);
    
    /**
     * @param int $nodeId
     * @return boolean
     */
    public function deleteBranch($nodeId);
    
    /**
     * @param int $nodeId
     * @param int $startLevel 0 = including root node
     * @param boolean $excludeLastNode
     * @return null|array
     */
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false);
    
    /**
     * @param int $nodeId
     * @return null|array
     */
    public function getNode($nodeId);
    
    /**
     * @param int $nodeId
     * @param int $startLevel Relative level from $nodeId. 1 = exclude $nodeId from result.
     *                        2 = exclude 2 levels from result
     * @param int $levels Number of levels in the results relative to $startLevel
     * @param int $excludeBranch Exclude defined branch(node id) from result
     * @return null|array
     */
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null);
    
    /**
     * @param int $nodeId
     * @return null|array
     */
    public function getChildren($nodeId);

    /**
     * Clear all data except root node
     *
     * @param array $data
     * @return $this
     */
    public function clear(array $data = array());
}