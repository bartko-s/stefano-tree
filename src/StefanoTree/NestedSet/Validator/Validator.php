<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Validator;

use Exception;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\Exception\TreeIsBrokenException;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;

class Validator implements ValidatorInterface
{
    private $adapter = null;

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
    private function _getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($rootNodeId): bool
    {
        $adapter = $this->_getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $rootNodeInfo = $this->_getAdapter()->getNodeInfo($rootNodeId);

            if (!$rootNodeInfo instanceof NodeInfo) {
                throw new InvalidArgumentException(
                    sprintf('Node with id "%s" does not exits', $rootNodeId)
                );
            }

            $this->_checkIfNodeIsRootNode($rootNodeInfo);
            $this->_rebuild($rootNodeInfo, true);

            $adapter->commitTransaction();
        } catch (TreeIsBrokenException $e) {
            $adapter->rollbackTransaction();

            return false;
        } catch (Exception $e) {
            $adapter->rollbackTransaction();
            throw $e;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rebuild($rootNodeId): void
    {
        $adapter = $this->_getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $rootNodeInfo = $this->_getAdapter()->getNodeInfo($rootNodeId);

            if (!$rootNodeInfo instanceof NodeInfo) {
                throw new InvalidArgumentException(
                    sprintf('Node with id "%s" does not exits', $rootNodeId)
                );
            }

            $this->_checkIfNodeIsRootNode($rootNodeInfo);
            $this->_rebuild($rootNodeInfo);

            $adapter->commitTransaction();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param NodeInfo $parentNodeInfo
     * @param bool     $onlyValidate
     * @param int      $left
     * @param int      $level
     *
     * @return int
     *
     * @throws TreeIsBrokenException if tree is broken and $onlyValidate is true
     */
    private function _rebuild(NodeInfo $parentNodeInfo, bool $onlyValidate = false, int $left = 1, int $level = 0): int
    {
        $adapter = $this->_getAdapter();

        $right = $left + 1;

        $children = $adapter->getChildrenNodeInfo($parentNodeInfo->getId());

        foreach ($children as $childNode) {
            $right = $this->_rebuild($childNode, $onlyValidate, $right, $level + 1);
        }

        if ($parentNodeInfo->getLeft() != $left
            || $parentNodeInfo->getRight() != $right
            || $parentNodeInfo->getLevel() != $level) {
            $parentNodeInfo->setLeft($left);
            $parentNodeInfo->setRight($right);
            $parentNodeInfo->setLevel($level);

            if ($onlyValidate) {
                throw new TreeIsBrokenException();
            } else {
                $adapter->updateNodeMetadata($parentNodeInfo);
            }
        }

        return $right + 1;
    }

    /**
     * @param NodeInfo $node
     *
     * @throws InvalidArgumentException
     */
    private function _checkIfNodeIsRootNode(NodeInfo $node): void
    {
        if (null != $node->getParentId()) {
            throw new InvalidArgumentException(
                sprintf('Given node id "%s" is not root id', $node->getId())
            );
        }
    }
}
