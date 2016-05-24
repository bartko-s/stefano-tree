<?php
namespace StefanoTree;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Exception;
use StefanoDb\Adapter\ExtendedAdapterInterface;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\Exception\RootNodeAlreadyExistException;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Adapter\Doctrine2DBALAdapter;
use StefanoTree\NestedSet\Adapter\Zend1DbAdapter;
use StefanoTree\NestedSet\Adapter\Zend2DbAdapter;
use StefanoTree\NestedSet\AddStrategy;
use StefanoTree\NestedSet\AddStrategy\AddStrategyInterface;
use StefanoTree\NestedSet\MoveStrategy;
use StefanoTree\NestedSet\MoveStrategy\MoveStrategyInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use Zend_Db_Adapter_Abstract;

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
    public static function factory(Options $options, $dbAdapter)
    {
        if ($dbAdapter instanceof ExtendedAdapterInterface) {
            $adapter = new Zend2DbAdapter($options, $dbAdapter);
        } elseif ($dbAdapter instanceof DoctrineConnection) {
            $adapter = new Doctrine2DBALAdapter($options, $dbAdapter);
        } elseif ($dbAdapter instanceof Zend_Db_Adapter_Abstract) {
            $adapter = new Zend1DbAdapter($options, $dbAdapter);
        } else {
            throw new InvalidArgumentException('Db adapter "' . get_class($dbAdapter)
                . '" is not supported');
        }

        return new self($adapter);
    }

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    public function createRootNode($data = array(), $scope=null)
    {
        if ($this->getRootNode($scope)) {
            throw new RootNodeAlreadyExistException(
                'Root node already exist'
            );
        }

        $nodeInfo = new NodeInfo(null, 0, 0, 1, 2, $scope);

        return $this->getAdapter()->insert($nodeInfo, $data);
    }

    /**
     * @param int $nodeId
     * @param array $data
     */
    public function updateNode($nodeId, $data)
    {
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
    protected function addNode($targetNodeId, $placement, $data = array())
    {
        $adapter = $this->getAdapter();

        $adapter->beginTransaction();
        try {
            $targetNode = $adapter->getNodeInfo($targetNodeId);
            if($targetNode) {
                $scope = $targetNode->getScope();
                $adapter->lockTree($scope);
            }

            $targetNode = $adapter->getNodeInfo($targetNodeId);

            if (null == $targetNode) {
                $adapter->commitTransaction();

                return false;
            }

            $addStrategy = $this->getAddStrategy($targetNode, $placement);

            if (false == $addStrategy->canAddNewNode()) {
                $adapter->commitTransaction();

                return false;
            }

            //make hole
            $moveFromIndex = $addStrategy->moveIndexesFromIndex();
            $adapter->moveLeftIndexes($moveFromIndex, 2, $targetNode->getScope());
            $adapter->moveRightIndexes($moveFromIndex, 2, $targetNode->getScope());

            //insert new node
            $newNodeInfo = new NodeInfo(
                null,
                $addStrategy->newParentId(),
                $addStrategy->newLevel(),
                $addStrategy->newLeftIndex(),
                $addStrategy->newRightIndex(),
                $targetNode->getScope()
            );
            $lastGeneratedValue = $adapter->insert($newNodeInfo, $data);

            $adapter->commitTransaction();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();

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
    private function getAddStrategy(NodeInfo $targetNode, $placement)
    {
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

    public function addNodePlacementBottom($targetNodeId, $data = array())
    {
        return $this->addNode($targetNodeId, self::PLACEMENT_BOTTOM, $data);
    }

    public function addNodePlacementTop($targetNodeId, $data = array())
    {
        return $this->addNode($targetNodeId, self::PLACEMENT_TOP, $data);
    }

    public function addNodePlacementChildBottom($targetNodeId, $data = array())
    {
        return $this->addNode($targetNodeId, self::PLACEMENT_CHILD_BOTTOM, $data);
    }

    public function addNodePlacementChildTop($targetNodeId, $data = array())
    {
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
    protected function moveNode($sourceNodeId, $targetNodeId, $placement)
    {
        $adapter = $this->getAdapter();

        //source node and target node are equal
        if ($sourceNodeId == $targetNodeId) {
            return false;
        }

        $adapter->beginTransaction();
        try {
            $sourceNode = $adapter->getNodeInfo($sourceNodeId);
            if($sourceNode) {
                $scope = $sourceNode->getScope();
                $adapter->lockTree($scope);
            }

            $sourceNodeInfo = $adapter->getNodeInfo($sourceNodeId);
            $targetNodeInfo = $adapter->getNodeInfo($targetNodeId);

            //source node or target node does not exist
            if (!$sourceNodeInfo || !$targetNodeInfo) {
                $adapter->commitTransaction();

                return false;
            }

            // scope are different
            if ($sourceNodeInfo->getScope() != $targetNodeInfo->getScope()) {
                throw new InvalidArgumentException('Cannot move node between scopes');
            }

            $moveStrategy = $this->getMoveStrategy($sourceNodeInfo, $targetNodeInfo, $placement);

            if (!$moveStrategy->canMoveBranch()) {
                $adapter->commitTransaction();

                return false;
            }

            if ($moveStrategy->isSourceNodeAtRequiredPosition()) {
                $adapter->commitTransaction();

                return true;
            }

            //update parent id
            $newParentId = $moveStrategy->getNewParentId();
            if ($sourceNodeInfo->getParentId() != $newParentId) {
                $adapter->updateParentId($sourceNodeId, $newParentId);
            }

            //update levels
            $adapter->updateLevels($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(),
                    $moveStrategy->getLevelShift(), $sourceNodeInfo->getScope());

            //make hole
            $adapter->moveLeftIndexes($moveStrategy->makeHoleFromIndex(),
                        $moveStrategy->getIndexShift(), $sourceNodeInfo->getScope());
            $adapter->moveRightIndexes($moveStrategy->makeHoleFromIndex(),
                        $moveStrategy->getIndexShift(), $sourceNodeInfo->getScope());

            //move branch to the hole
            $adapter->moveBranch($moveStrategy->getHoleLeftIndex(), $moveStrategy->getHoleRightIndex(),
                $moveStrategy->getSourceNodeIndexShift(), $sourceNodeInfo->getScope());

            //patch hole
            $adapter->moveLeftIndexes($moveStrategy->fixHoleFromIndex(),
                        ($moveStrategy->getIndexShift() * -1), $sourceNodeInfo->getScope());
            $adapter->moveRightIndexes($moveStrategy->fixHoleFromIndex(),
                        ($moveStrategy->getIndexShift() * -1), $sourceNodeInfo->getScope());

            $adapter->commitTransaction();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();

            throw $e;
        }

        return true;
    }

    public function moveNodePlacementBottom($sourceNodeId, $targetNodeId)
    {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_BOTTOM);
    }

    public function moveNodePlacementTop($sourceNodeId, $targetNodeId)
    {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_TOP);
    }

    public function moveNodePlacementChildBottom($sourceNodeId, $targetNodeId)
    {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_CHILD_BOTTOM);
    }

    public function moveNodePlacementChildTop($sourceNodeId, $targetNodeId)
    {
        return $this->moveNode($sourceNodeId, $targetNodeId, self::PLACEMENT_CHILD_TOP);
    }

    /**
     * @param NodeInfo $sourceNode
     * @param NodeInfo $targetNode
     * @param string $placement
     * @return MoveStrategyInterface
     * @throws InvalidArgumentException
     */
    private function getMoveStrategy(NodeInfo $sourceNode, NodeInfo $targetNode, $placement)
    {
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

    public function deleteBranch($nodeId)
    {
        $adapter = $this->getAdapter();

        $adapter->beginTransaction();
        try {
            $node = $adapter->getNodeInfo($nodeId);
            if($node) {
                $scope = $node->getScope();
                $adapter->lockTree($scope);
            }

            $nodeInfo = $adapter->getNodeInfo($nodeId);

            // node does not exist
            if (!$nodeInfo) {
                $adapter->commitTransaction();

                return false;
            }

            // delete branch
            $leftIndex = $nodeInfo->getLeft();
            $rightIndex = $nodeInfo->getRight();
            $adapter->delete($leftIndex, $rightIndex, $nodeInfo->getScope());

            //patch hole
            $moveFromIndex = $nodeInfo->getLeft();
            $shift = $nodeInfo->getLeft() - $nodeInfo->getRight() - 1;
            $adapter->moveLeftIndexes($moveFromIndex, $shift, $nodeInfo->getScope());
            $adapter->moveRightIndexes($moveFromIndex, $shift, $nodeInfo->getScope());

            $adapter->commitTransaction();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();

            throw $e;
        }

        return true;
    }

    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false)
    {
        return $this->getAdapter()
                    ->getPath($nodeId, $startLevel, $excludeLastNode);
    }

    public function getNode($nodeId)
    {
        return $this->getAdapter()
                    ->getNode($nodeId);
    }

    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        return $this->getAdapter()
                    ->getDescendants($nodeId, $startLevel, $levels, $excludeBranch);
    }

    public function getChildren($nodeId)
    {
        return $this->getDescendants($nodeId, 1, 1);
    }

    public function getRootNode($scope=null)
    {
        return $this->getAdapter()
                    ->getRoot($scope);
    }

    public function getRoots()
    {
        return $this->getAdapter()
                    ->getRoots();
    }
}
