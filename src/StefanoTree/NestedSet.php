<?php

namespace StefanoTree;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Exception;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\Exception\RootNodeAlreadyExistException;
use StefanoTree\NestedSet\Adapter;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\AddStrategy;
use StefanoTree\NestedSet\AddStrategy\AddStrategyInterface;
use StefanoTree\NestedSet\MoveStrategy;
use StefanoTree\NestedSet\MoveStrategy\MoveStrategyInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use StefanoTree\NestedSet\Validator\Validator;
use StefanoTree\NestedSet\Validator\ValidatorInterface;
use Zend\Db\Adapter\Adapter as Zend2DbAdapter;

class NestedSet implements TreeInterface
{
    private $adapter;

    private $validator;

    /**
     * @param Options $options
     * @param object  $dbAdapter
     *
     * @return TreeInterface
     *
     * @throws InvalidArgumentException
     */
    public static function factory(Options $options, $dbAdapter)
    {
        if ($dbAdapter instanceof Zend2DbAdapter) {
            $adapter = new Adapter\Zend2($options, $dbAdapter);
        } elseif ($dbAdapter instanceof DoctrineConnection) {
            $adapter = new Adapter\Doctrine2DBAL($options, $dbAdapter);
        } elseif ($dbAdapter instanceof \Zend_Db_Adapter_Abstract) {
            $adapter = new Adapter\Zend1($options, $dbAdapter);
        } else {
            throw new InvalidArgumentException('Db adapter "'.get_class($dbAdapter)
                .'" is not supported');
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

    /**
     * @return ValidatorInterface
     */
    private function _getValidator()
    {
        if (null == $this->validator) {
            $this->validator = new Validator($this->getAdapter());
        }

        return $this->validator;
    }

    public function createRootNode($data = array(), $scope = null)
    {
        if ($this->getRootNode($scope)) {
            if ($scope) {
                $errorMessage = sprintf('Root node for scope "%s" already exist', $scope);
            } else {
                $errorMessage = 'Root node already exist';
            }

            throw new RootNodeAlreadyExistException($errorMessage);
        }

        $nodeInfo = new NodeInfo(null, null, 0, 1, 2, $scope);

        return $this->getAdapter()->insert($nodeInfo, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateNode($nodeId, array $data)
    {
        $this->getAdapter()
             ->update($nodeId, $data);
    }

    /**
     * @param int    $targetNodeId
     * @param string $placement
     * @param array  $data
     *
     * @return int|null Id of new created node. Null if node has not been created
     */
    protected function addNode($targetNodeId, string $placement, array $data = array())
    {
        return $this->getAddStrategy($placement)->add($targetNodeId, $data);
    }

    /**
     * @param string $placement
     *
     * @return AddStrategyInterface
     *
     * @throws InvalidArgumentException
     */
    protected function getAddStrategy(string $placement): AddStrategyInterface
    {
        $adapter = $this->getAdapter();

        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new AddStrategy\Bottom($adapter);
            case self::PLACEMENT_TOP:
                return new AddStrategy\Top($adapter);
            case self::PLACEMENT_CHILD_BOTTOM:
                return new AddStrategy\ChildBottom($adapter);
            case self::PLACEMENT_CHILD_TOP:
                return new AddStrategy\ChildTop($adapter);
            default:
                throw new InvalidArgumentException('Unknown placement "'.$placement.'"');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addNodePlacementBottom($targetNodeId, array $data = array())
    {
        return $this->addNode($targetNodeId, self::PLACEMENT_BOTTOM, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addNodePlacementTop($targetNodeId, array $data = array())
    {
        return $this->addNode($targetNodeId, self::PLACEMENT_TOP, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addNodePlacementChildBottom($targetNodeId, array $data = array())
    {
        return $this->addNode($targetNodeId, self::PLACEMENT_CHILD_BOTTOM, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addNodePlacementChildTop($targetNodeId, array $data = array())
    {
        return $this->addNode($targetNodeId, self::PLACEMENT_CHILD_TOP, $data);
    }

    /**
     * @param int    $sourceNodeId
     * @param int    $targetNodeId
     * @param string $placement
     *
     * @return bool
     */
    protected function moveNode($sourceNodeId, $targetNodeId, string $placement): bool
    {
        return $this->getMoveStrategy($placement)->move($sourceNodeId, $targetNodeId);
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
     * @param string $placement
     *
     * @return MoveStrategyInterface
     *
     * @throws InvalidArgumentException
     */
    protected function getMoveStrategy(string $placement): MoveStrategyInterface
    {
        $adapter = $this->getAdapter();

        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new MoveStrategy\Bottom($adapter);
            case self::PLACEMENT_TOP:
                return new MoveStrategy\Top($adapter);
            case self::PLACEMENT_CHILD_BOTTOM:
                return new MoveStrategy\ChildBottom($adapter);
            case self::PLACEMENT_CHILD_TOP:
                return new MoveStrategy\ChildTop($adapter);
            default:
                throw new InvalidArgumentException('Unknown placement "'.$placement.'"');
        }
    }

    public function deleteBranch($nodeId)
    {
        $adapter = $this->getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $nodeInfo = $adapter->getNodeInfo($nodeId);

            // node does not exist
            if (!$nodeInfo) {
                $adapter->commitTransaction();

                return false;
            }

            // delete branch
            $adapter->delete($nodeInfo->getId());

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

    /**
     * {@inheritdoc}
     */
    public function getDescendants($nodeId, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        return $this->getAdapter()
                    ->getDescendants($nodeId, $startLevel, $levels, $excludeBranch);
    }

    public function getChildren($nodeId)
    {
        return $this->getDescendants($nodeId, 1, 1);
    }

    public function getRootNode($scope = null)
    {
        return $this->getAdapter()
                    ->getRoot($scope);
    }

    public function getRoots()
    {
        return $this->getAdapter()
                    ->getRoots();
    }

    public function isValid($rootNodeId)
    {
        return $this->_getValidator()
                    ->isValid($rootNodeId);
    }

    public function rebuild($rootNodeId)
    {
        $this->_getValidator()
             ->rebuild($rootNodeId);
    }
}
