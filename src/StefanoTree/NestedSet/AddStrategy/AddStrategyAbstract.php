<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\AddStrategy;

use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet\Manipulator\ManipulatorInterface;
use StefanoTree\NestedSet\NodeInfo;

abstract class AddStrategyAbstract implements AddStrategyInterface
{
    public function __construct(
        private readonly ManipulatorInterface $manipulator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function add(int|string $targetNodeId, array $data = array()): int|string
    {
        $adapter = $this->getManipulator();

        $adapter->beginTransaction();

        try {
            $adapter->lockTree();

            $targetNodeInfo = $adapter->getNodeInfo($targetNodeId);

            if (!$targetNodeInfo instanceof NodeInfo) {
                throw new ValidationException('Target Node does not exists.');
            }

            $this->canCreateNewNode($targetNodeInfo);
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
     * @throws ValidationException If cannot move node
     */
    abstract protected function canCreateNewNode(NodeInfo $targetNode): void;

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

    protected function getManipulator(): ManipulatorInterface
    {
        return $this->manipulator;
    }
}
