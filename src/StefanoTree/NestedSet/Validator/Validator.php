<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Validator;

use Exception;
use StefanoTree\Exception\TreeIsBrokenException;
use StefanoTree\Exception\ValidationException;
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
    private function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($rootNodeId): bool
    {
        $adapter = $this->getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $rootNodeInfo = $this->getAdapter()->getNodeInfo($rootNodeId);

            if (!$rootNodeInfo instanceof NodeInfo) {
                throw new ValidationException('Node does not exists.');
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
        $adapter = $this->getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $rootNodeInfo = $this->getAdapter()->getNodeInfo($rootNodeId);

            if (!$rootNodeInfo instanceof NodeInfo) {
                throw new ValidationException('Node does not exists.');
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
        $adapter = $this->getAdapter();

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
     * @throws ValidationException
     */
    private function _checkIfNodeIsRootNode(NodeInfo $node): void
    {
        if (null != $node->getParentId()) {
            throw new ValidationException('Given node is not root node.');
        }
    }
}
