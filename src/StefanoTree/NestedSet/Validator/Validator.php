<?php
namespace StefanoTree\NestedSet\Validator;

use Exception;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;

class Validator
    implements ValidatorInterface
{
    private $isValid = true;

    private $tree = array();

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
    private function _getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param NodeInfo $nodeInfo
     * @param array $_tree
     * @return array
     */
    private function _buildFlatTree(NodeInfo $nodeInfo, $_tree = array())
    {
        $children = $this->_getAdapter()
                         ->getChildrenNodeInfo($nodeInfo->getId());

        $_tree[] = $nodeInfo;

        foreach ($children as $child) {
            $_tree = $this->_buildFlatTree($child, $_tree);
        }

        return $_tree;
    }

    /**
     * @param array $tree
     * @return array
     */
    private function _validateLevels(array $tree)
    {
        $nodeIdVsLevel = array(0 => -1);

        foreach ($tree as &$nodeInfo) {
            $parentId = $nodeInfo->getParentId() ? $nodeInfo->getParentId() : 0;

            $nodeIdVsLevel[$nodeInfo->getId()] = $nodeIdVsLevel[$parentId] + 1;

            $currentLevel = $nodeInfo->getLevel();
            $expectedLevel = $nodeIdVsLevel[$parentId] + 1;

            if ($currentLevel != $expectedLevel) {
                $nodeInfo->setLevel($expectedLevel);
                $nodeInfo->setNeedUpdate(true);

                $this->isValid = false;
            }
        }

        return $tree;
    }

    /**
     * @param array $tree
     * @return array
     */
    private function _validateLeftIndexes(array $tree)
    {
        $expectedLeftIdx = -1;
        $prevLevel = 0;

        foreach ($tree as &$nodeInfo) {
            $currentLeftIdx = $nodeInfo->getLeft();

            if ($nodeInfo->getLevel() == $prevLevel) {
                $expectedLeftIdx += 2;
            } elseif ($nodeInfo->getLevel() > $prevLevel) {
                $expectedLeftIdx += 1;
            } else {
                $expectedLeftIdx += $prevLevel - $nodeInfo->getLevel() + 2;
            }

            if ($currentLeftIdx != $expectedLeftIdx) {
                $nodeInfo->setLeft($expectedLeftIdx);
                $nodeInfo->setNeedUpdate(true);

                $this->isValid = false;
            }

            $prevLevel = $nodeInfo->getLevel();
        }

        return $tree;
    }

    /**
     * @param array $tree
     * @return array
     */
    private function _validateRightIndexes(array $tree)
    {
        $prevLevel = -1;
        $endNodes = array();

        for ($x = count($tree); $x > 0; $x--) {
            $nodeInfo = $tree[$x - 1];
            if (-1 == $prevLevel || $prevLevel == $nodeInfo->getLevel()) {
                $expectedRightIdx = $nodeInfo->getLeft() + 1;
            } elseif ($prevLevel > $nodeInfo->getLevel()) {
                $currentLevel = $nodeInfo->getLevel();

                if (array_key_exists($currentLevel + 1, $endNodes)) {
                    $expectedRightIdx = $endNodes[$currentLevel + 1] + 1;
                    unset($endNodes[$currentLevel + 1]);
                } else {
                    $expectedRightIdx = $tree[$x]->getRight() + 1;
                }
            } else {
                $expectedRightIdx = $nodeInfo->getLeft() + 1;
            }

            $currentRightIdx = $nodeInfo->getRight();

            if ($currentRightIdx != $expectedRightIdx) {
                $nodeInfo->setRight($expectedRightIdx);
                $nodeInfo->setNeedUpdate(true);
                $tree[$x - 1] = $nodeInfo;

                $this->isValid = false;
            }

            if (!array_key_exists($nodeInfo->getLevel(), $endNodes)) {
                $endNodes[$nodeInfo->getLevel()] = $nodeInfo->getRight();
            }

            $prevLevel = $nodeInfo->getLevel();
        }

        return $tree;
    }

    public function isValid($rootNodeId)
    {
        $adapter = $this->_getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $result = $this->_isValid($rootNodeId);

            $adapter->commitTransaction();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();
            throw $e;
        }

        return $result;
    }

    /**
     * @param $rootNodeId
     * @return bool True if tree is valid
     */
    protected function _isValid($rootNodeId)
    {
        $this->isValid = True;

        $rootNodeInfo = $this->_getAdapter()->getNodeInfo($rootNodeId);
        $tree = $this->_buildFlatTree($rootNodeInfo);

        $tree = $this->_validateLevels($tree);
        $tree = $this->_validateLeftIndexes($tree);
        $tree = $this->_validateRightIndexes($tree);

        $this->tree = $tree;

        return $this->isValid;
    }

    public function rebuild($rootNodeId)
    {
        $adapter = $this->_getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $this->_isValid($rootNodeId);

            if ($this->isValid) {
                $adapter->commitTransaction();
                return;
            }

            foreach ($this->tree as $nodeInfo) {
                if ($nodeInfo->needUpdate()) {
                    $this->_getAdapter()
                         ->updateNodeMetadata($nodeInfo);
                }
            }
            $adapter->commitTransaction();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();
            throw $e;
        }
    }
}
