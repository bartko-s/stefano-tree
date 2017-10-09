<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;

abstract class AddStrategyAbstract implements AddStrategyInterface
{
    private $adapter;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function add($targetNodeId, array $data = array())
    {
        $adapter = $this->getAdapter();

        $adapter->beginTransaction();
        try {
            $adapter->lockTree();

            $targetNodeInfo = $adapter->getNodeInfo($targetNodeId);

            if (!$targetNodeInfo instanceof NodeInfo) {
                $adapter->commitTransaction();

                return null;
            }

            if (false == $this->canCreateNewNode($targetNodeInfo)) {
                $adapter->commitTransaction();

                return null;
            }

            $this->makeHole($targetNodeInfo);
            $newNodeId = $adapter->insert($this->createNewNodeNodeInfo($targetNodeInfo), $data);

            $adapter->commitTransaction();
        } catch (\Exception $e) {
            $adapter->rollbackTransaction();

            throw $e;
        }

        return $newNodeId;
    }

    /**
     * @param NodeInfo $targetNode
     *
     * @return bool
     */
    abstract protected function canCreateNewNode(NodeInfo $targetNode): bool;

    /**
     * @param NodeInfo $targetNode
     */
    abstract protected function makeHole(NodeInfo $targetNode): void;

    /**
     * @param NodeInfo $targetNode
     *
     * @return NodeInfo
     */
    abstract protected function createNewNodeNodeInfo(NodeInfo $targetNode): NodeInfo;

    /**
     * @return AdapterInterface
     */
    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }
}
