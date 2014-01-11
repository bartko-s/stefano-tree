<?php
namespace StefanoTree\DbTraversal\AddStrategy;

use StefanoTree\DbTraversal\NodeInfo;
use StefanoTree\DbTraversal\AddStrategy\AddStrategyInterface;

abstract class AddStrategyAbstract
    implements AddStrategyInterface
{
    protected $targetNode;

    /**
     * @param NodeInfo $targetNode
     */
    public function __construct(NodeInfo $targetNode) {
        $this->targetNode = $targetNode;
    }

    /**
     * @return NodeInfo
     */
    protected function getTargetNode() {
        return $this->targetNode;
    }

    public function canAddNewNode($rootNodeId) {
        return true;
    }
}
