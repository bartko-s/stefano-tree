<?php
namespace StefanoTree;

use StefanoTree\NestedSet\NodeInfo;
use Exception;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\NestedSet\AddStrategy;
use StefanoTree\NestedSet\AddStrategy\AddStrategyInterface;
use StefanoTree\NestedSet\MoveStrategy;
use StefanoTree\NestedSet\MoveStrategy\MoveStrategyInterface;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Options;
use StefanoTree\NestedSet\Adapter\Doctrine2DBALAdapter;
use StefanoTree\NestedSet\Adapter\Zend2DbAdapter;
use StefanoDb\Adapter\ExtendedAdapterInterface;
use Doctrine\DBAL\Connection as DoctrineConnection;

class NestedSet
    implements TreeInterface
{
    private $adapter;

    /**
     * @param Options $options
     * @param object $dbAdapter
     * @return TreeInterface
     * @throws InvalidArgumentException
     */
    static public function factory(Options $options, $dbAdapter) {
        if($dbAdapter instanceof ExtendedAdapterInterface) {
            $adapter = new Zend2DbAdapter($options, $dbAdapter);
        } elseif($dbAdapter instanceof  DoctrineConnection) {
            $adapter = new Doctrine2DBALAdapter($options, $dbAdapter);
        } else {
            throw new InvalidArgumentException('Db adapter "' . get_class($dbAdapter)
                . '" is not supported');
        }

        return new self($adapter);
    }

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter() {
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

        $adapter->beginTransaction();
        try {
            $adapter->lockTable();

            $targetNode = $adapter->getNodeInfo($targetNodeId);

            if(null == $targetNode) {
                $adapter->commitTransaction();
                $adapter->unlockTable();

                return false;
            }

            $addStrategy = $this->getAddStrategy($targetNode, $placement);

            if(false == $addStrategy->canAddNewNode($this->getRootNodeId())) {
                $adapter->commitTransaction();
                $adapter->unlockTable();

                return false;
            }

            //make hole
            $moveFromIndex = $addStrategy->moveIndexesFromIndex($targetNode);
            $adapter->moveLeftIndexes($moveFromIndex, 2);
            $adapter->moveRightIndexes($moveFromIndex, 2);

            //insert new node
            $newNodeInfo = new NodeInfo(
                null,
                $addStrategy->newParentId(),
                $addStrategy->newLevel(),
                $addStrategy->newLeftIndex(),
                $addStrategy->newRightIndex()
            );
            $lastGeneratedValue = $adapter->insert($newNodeInfo, $data);

            $adapter->commitTransaction();
            $adapter->unlockTable();
        } catch(Exception $e) {
            $adapter->rollbackTransaction();
            $adapter->unlockTable();

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

        $adapter->beginTransaction();
        try {
            $adapter->lockTable();
            
            //source node or target node does not exist
            if(!$sourceNodeInfo = $adapter->getNodeInfo($sourceNodeId)
                OR !$targetNodeInfo = $adapter->getNodeInfo($targetNodeId)) {
                $adapter->commitTransaction();
                $adapter->unlockTable();

                return false;
            }

            $moveStrategy = $this->getMoveStrategy($sourceNodeInfo, $targetNodeInfo, $placement);

            if(!$moveStrategy->canMoveBranch($this->getRootNodeId())) {
                $adapter->commitTransaction();
                $adapter->unlockTable();
                
                return false;
            }
                        
            if($moveStrategy->isSourceNodeAtRequiredPosition()) {
                $adapter->commitTransaction();
                $adapter->unlockTable();

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
                        $moveStrategy->getIndexShift());
            $adapter->moveRightIndexes($moveStrategy->makeHoleFromIndex(),
                        $moveStrategy->getIndexShift());

            //move branch to the hole
            $adapter->moveBranch($moveStrategy->getHoleLeftIndex(),
                $moveStrategy->getHoleRightIndex(), $moveStrategy->getSourceNodeIndexShift());

            //patch hole
            $adapter->moveLeftIndexes($moveStrategy->fixHoleFromIndex(),
                        ($moveStrategy->getIndexShift() * -1));
            $adapter->moveRightIndexes($moveStrategy->fixHoleFromIndex(),
                        ($moveStrategy->getIndexShift() * -1));

            $adapter->commitTransaction();
            $adapter->unlockTable();
        } catch(Exception $e) {
            $adapter->rollbackTransaction();
            $adapter->unlockTable();
            
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

        $adapter->beginTransaction();
        try {
            $adapter->lockTable();
            
            // node does not exist
            if(!$nodeInfo = $adapter->getNodeInfo($nodeId)) {
                $adapter->commitTransaction();
                $adapter->unlockTable();
                
                return false;
            }

            // delete branch
            $leftIndex = $nodeInfo->getLeft();
            $rightIndex = $nodeInfo->getRight();
            $adapter->delete($leftIndex, $rightIndex);

            //patch hole
            $moveFromIndex = $nodeInfo->getLeft();
            $shift = $nodeInfo->getLeft() - $nodeInfo->getRight() - 1;
            $adapter->moveLeftIndexes($moveFromIndex, $shift);
            $adapter->moveRightIndexes($moveFromIndex, $shift);

            $adapter->commitTransaction();
            $adapter->unlockTable();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();
            $adapter->unlockTable();

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

        $adapter->beginTransaction();
        try {
            $adapter->lockTable();

            $adapter->deleteAll($this->getRootNodeId());

            $nodeInfo = new NodeInfo(null, 0, 0, 1, 2);
            $adapter->update($this->getRootNodeId(), $data, $nodeInfo);

            $adapter->commitTransaction();
            $adapter->unlockTable();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();
            $adapter->unlockTable();

            throw $e;
        }
        
        return $this;
    }        
    
    public function getNode($nodeId) {
        return $this->getAdapter()
                    ->getNode($nodeId);
    }
        
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null) {
        return $this->getAdapter()
                    ->getDescendants($nodeId, $startLevel, $levels, $excludeBranch);

    }    
    
    public function getChildren($nodeId) {
        return $this->getDescendants($nodeId, 1, 1);
    }
}
