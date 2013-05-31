<?php
namespace StefanoTree\Adapter;

interface AdapterInterface
{   
    const PLACEMENT_TOP = 'top';
    const PLACEMENT_BOTTOM = 'bottom';
    const PLACEMENT_CHILD_TOP = 'childTop';
    const PLACEMENT_CHILD_BOTTOM = 'childBottom';
    
    /**
     * 
     * @param int $nodeId
     * @param array $data
     */
    public function updateNode($nodeId, $data);
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false last insert id
     */
    public function addNodePlacementBottom($targetNodeId, $data = array());
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false last insert id
     */
    public function addNodePlacementTop($targetNodeId, $data = array());
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false last insert id
     */
    public function addNodePlacementChildBottom($targetNodeId, $data = array());
    
    /**
     * @param int $targetNodeId
     * @param array $data
     * @return int|false last insert id
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
     * 
     * @param int $nodeId
     * @return boolean
     * @throws \Exception
     */
    public function deleteBranch($nodeId);
    
    /**
     * 
     * @param int $nodeId
     * @param int $startLevel 0 = vratane root
     * @param bolean $excludeLastNode
     * @return null|array
     */
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false);
    
    /**
     * @param int $nodeId
     * @return null|array
     */
    public function getNode($nodeId);
    
    /**
     * 
     * @param int $nodeId
     * @param int $startLevel relativny level od $nodeId. 0 = vratane $nodeId
     * @param int $levels levelov vo vysledku
     * @param int $excludeBranche nenacitat vetvu
     * @return null|array
     */
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranche = null);
    
    /**
     * Vrati priamych potomkov uzla
     * @param int $nodeId
     * @return null|array
     */
    public function getChildren($nodeId);    
}