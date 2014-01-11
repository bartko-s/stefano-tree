<?php
namespace StefanoTree;

use StefanoTree\NestedSet\NodeInfo;
use Exception;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\NestedSet\AddStrategy;
use StefanoTree\NestedSet\AddStrategy\AddStrategyInterface;
use StefanoTree\NestedSet\MoveStrategy;
use StefanoTree\NestedSet\MoveStrategy\MoveStrategyInterface;

class NestedSet
    implements AdapterInterface
{
    private $adapter;

    /**
     * @param \StefanoTree\NestedSet\Adapter\AdapterInterface $adapter
     * @throws InvalidArgumentException
     */
    public function __construct(\StefanoTree\NestedSet\Adapter\AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    /**
     * @return \StefanoTree\NestedSet\Adapter\AdapterInterface
     */
    private function getAdapter() {
        return $this->adapter;
    }

    /**
     * @return int
     */
    private function getRootNodeId() {
        return 1;
    }

    /**
     * Test if node is root node
     *
     * @param int $nodeId
     * @return boolean
     */
    private function isRoot($nodeId) {
        if($this->getRootNodeId() == $nodeId) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @param int $nodeId
     * @param array $data
     */
    public function updateNode($nodeId, $data) {
        $this->getAdapter()
             ->update($nodeId, $data);
    }
    
    /**
     * @param int $targetNodeId
     * @param string $placement
     * @param array $data
     * @return int|false Id of new created node. False if node has not been created
     * @throws Exception
     */
    protected function addNode($targetNodeId, $placement, $data = array()) {
        $adapter = $this->getAdapter();
        
        try {
            $adapter->beginTransaction()
                    ->lockTable();

            $targetNode = $adapter->getNodeInfo($targetNodeId);

            if(null == $targetNode) {
                $adapter->commitTransaction()
                        ->unlockTable();
                return false;
            }

            $addStrategy = $this->getAddStrategy($targetNode, $placement);

            if(false == $addStrategy->canAddNewNode($this->getRootNodeId())) {
                $adapter->commitTransaction()
                        ->unlockTable();
                return false;
            }

            //make hole
            $moveFromIndex = $addStrategy->moveIndexesFromIndex($targetNode);
            $adapter->moveLeftIndexes($moveFromIndex, 2)
                    ->moveRightIndexes($moveFromIndex, 2);

            //insert new node
            $newNodeInfo = new NodeInfo(array(
                'id'        => null,
                'parentId'  => $addStrategy->newParentId(),
                'level'     => $addStrategy->newLevel(),
                'left'      => $addStrategy->newLeftIndex(),
                'right'     => $addStrategy->newRightIndex(),
            ));
            $lastGeneratedValue = $adapter->insert($newNodeInfo, $data);

            $adapter->commitTransaction()
                    ->unlockTable();
        } catch(Exception $e) {
            $adapter->rollbackTransaction()
                    ->unlockTable();
            throw $e;
        }
            
        return $lastGeneratedValue;
    }

    /**
     * @param NodeInfo $targetNode
     * @param string $placement
     * @return AddStrategyInterface
     * @throws InvalidArgumentException
     */
    private function getAddStrategy(NodeInfo $targetNode, $placement) {
        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new AddStrategy\Bottom($targetNode);
            case self::PLACEMENT_TOP:
                return new AddStrategy\Top($targetNode);
            case self::PLACEMENT_CHILD_BOTTOM:
                return new AddStrategy\ChildBottom($targetNode);
            case self::PLACEMENT_CHILD_TOP:
                return new AddStrategy\ChildTop($targetNode);
        // @codeCoverageIgnoreStart
            default:
                throw new InvalidArgumentException('Unknown placement "' . $placement . '"');                
        }
        // @codeCoverageIgnoreEnd
    }

    public function addNodePlacementBottom($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_BOTTOM, $data);
    }
    
    public function addNodePlacementTop($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_TOP, $data);
    }
    
    public function addNodePlacementChildBottom($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_CHILD_BOTTOM, $data);
    }
    
    public function addNodePlacementChildTop($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_CHILD_TOP, $data);
    }

    /**
     * @param int $sourceNodeId
     * @param int $targetNodeId
     * @param string $placement
     * @return boolean
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function moveNode($sourceNodeId, $targetNodeId, $placement) {
        $adapter = $this->getAdapter();
                
        //source node and target node are equal
        if($sourceNodeId == $targetNodeId) {
            return false;
        }
        
        try {
            $adapter->beginTransaction()
                    ->lockTable();
            
            //source node or target node does not exist
            if(!$sourceNodeInfo = $adapter->getNodeInfo($sourceNodeId)
                XOR !$targetNodeInfo = $adapter->getNodeInfo($targetNodeId)) {
                $adapter->commitTransaction()
                        ->unlockTable();

                return false;
            }

            $moveStrategy = $this->getMoveStrategy($sourceNodeInfo, $targetNodeInfo, $placement);

            if(!$moveStrategy->canMoveBranche($this->getRootNodeId())) {
                $adapter->commitTransaction()
                        ->unlockTable();
                
                return false;
            }
                        
            if($moveStrategy->isSourceNodeAtRequiredPossition()) {
                $adapter->commitTransaction()
                        ->unlockTable();

                return true;
            }

            //update parent id
            $newParentId = $moveStrategy->getNewParentId();
            if($sourceNodeInfo->getParentId() != $newParentId) {
                $adapter->updateParentId($sourceNodeId, $newParentId);
            }

            //update levels
            $adapter->updateLevels($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(),
                    $moveStrategy->getLevelShift());

            //make hole
            $adapter->moveLeftIndexes($moveStrategy->makeHoleFromIndex(),
                        $moveStrategy->getIndexShift())
                    ->moveRightIndexes($moveStrategy->makeHoleFromIndex(),
                        $moveStrategy->getIndexShift());

            //move branche to the hole
            $adapter->moveBranch($moveStrategy->getHoleLeftIndex(),
                $moveStrategy->getHoleRightIndex(), $moveStrategy->getSourceNodeIndexShift());

            //patch hole
            $adapter->moveLeftIndexes($moveStrategy->fixHoleFromIndex(),
                        ($moveStrategy->getIndexShift() * -1))
                    ->moveRightIndexes($moveStrategy->fixHoleFromIndex(),
                        ($moveStrategy->getIndexShift() * -1));

            $adapter->commitTransaction()
                    ->unlockTable();
        } catch(Exception $e) {
            $adapter->rollbackTransaction()
                    ->unlockTable();
            
            throw $e;
        }
        
        return true;
    }
    
    public function moveNodePlacementBottom($sourceNodeId, $targetNodeId) {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_BOTTOM);
    }

    public function moveNodePlacementTop($sourceNodeId, $targetNodeId) {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_TOP);
    }

    public function moveNodePlacementChildBottom($sourceNodeId, $targetNodeId) {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_CHILD_BOTTOM);
    }    
    
    public function moveNodePlacementChildTop($sourceNodeId, $targetNodeId) {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_CHILD_TOP);
    }

    /**
     * @param NodeInfo $sourceNode
     * @param NodeInfo $targetNode
     * @param string $placement
     * @return MoveStrategyInterface
     * @throws InvalidArgumentException
     */
    private function getMoveStrategy(NodeInfo $sourceNode, NodeInfo $targetNode, $placement) {
        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new MoveStrategy\Bottom($sourceNode, $targetNode);
            case self::PLACEMENT_TOP:
                return new MoveStrategy\Top($sourceNode, $targetNode);
            case self::PLACEMENT_CHILD_BOTTOM:
                return new MoveStrategy\ChildBottom($sourceNode, $targetNode);
            case self::PLACEMENT_CHILD_TOP:
                return new MoveStrategy\ChildTop($sourceNode, $targetNode);
        // @codeCoverageIgnoreStart
            default:
                throw new InvalidArgumentException('Unknown placement "' . $placement . '"');
        }
        // @codeCoverageIgnoreEnd
    }
    
    public function deleteBranch($nodeId) {
        if($this->isRoot($nodeId)) {
            return false;
        }

        $adapter = $this->getAdapter();
        
        try {
            $adapter->beginTransaction()
                    ->lockTable();
            
            // node does not exist
            if(!$nodeInfo = $adapter->getNodeInfo($nodeId)) {
                $adapter->commitTransaction()
                        ->unlockTable();
                
                return false;
            }

            // delete branch
            $leftIndex = $nodeInfo->getLeft();
            $rightIndex = $nodeInfo->getRight();
            $adapter->delete($leftIndex, $rightIndex);

            //patch hole
            $moveFromIndex = $nodeInfo->getLeft();
            $shift = $nodeInfo->getLeft() - $nodeInfo->getRight() - 1;
            $adapter->moveLeftIndexes($moveFromIndex, $shift)
                    ->moveRightIndexes($moveFromIndex, $shift);

            $adapter->commitTransaction()
                    ->unlockTable();
        } catch (Exception $e) {
            $adapter->rollbackTransaction()
                    ->unlockTable();
            throw $e;
        }
        
        return true;
    }
    
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false) {
        return $this->getAdapter()
                    ->getPath($nodeId, $startLevel, $excludeLastNode);
    }
    
    public function clear(array $data = array()) {
        $adapter = $this->getAdapter();

        try {
            $adapter->beginTransaction()
                    ->lockTable();

            $adapter->deleteAll($this->getRootNodeId());

            $nodeInfo = new NodeInfo(array(
                'id'        => null,
                'parentId'  => 0,
                'level'     => 0,
                'left'      => 1,
                'right'     => 2,
            ));
            $adapter->update($this->getRootNodeId(), $data, $nodeInfo);

            $adapter->commitTransaction()
                    ->unlockTable();
        } catch (Exception $e) {
            $adapter->rollbackTransaction()
                    ->unlockTable();
            throw $e;
        }
        
        return $this;
    }        
    
    public function getNode($nodeId) {
        return $this->getAdapter()
                    ->getNode($nodeId);
    }
        
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranche = null) {
        return $this->getAdapter()
                    ->getDescendants($nodeId, $startLevel, $levels, $excludeBranche);

    }    
    
    public function getChildren($nodeId) {
        return $this->getDescendants($nodeId, 1, 1);
    }
}