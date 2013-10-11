<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\Helper\NodeInfo;
use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyInterface;

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
