<?php
namespace StefanoTree\Adapter\DbTraversal\MoveStrategy;

use StefanoTree\Adapter\Helper\NodeInfo;
use StefanoTree\Adapter\DbTraversal\MoveStrategy\MoveStrategyInterface;

abstract class MoveStrategyAbstract
    implements MoveStrategyInterface
{
    protected $sourceNode;
    protected $targetNode;

    /**
     * @param NodeInfo $sourceNode
     * @param NodeInfo $targetNode
     */
    public function __construct(NodeInfo $sourceNode, NodeInfo $targetNode) {
        $this->sourceNode = $sourceNode;
        $this->targetNode = $targetNode;
    }

    /**
     * @return NodeInfo
     */
    protected function getSourceNode() {
        return $this->sourceNode;
    }

    /**
     * @return NodeInfo
     */
    protected function getTargetNode() {
        return $this->targetNode;
    }

    /**
     * @return bolean
     */
    protected function isMovedUp() {
        if($this->getTargetNode()->getRight() < $this->getSourceNode()->getLeft()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bolean
     */
    protected function isMovedDown() {
        if($this->getSourceNode()->getRight() < $this->getTargetNode()->getLeft()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bolean
     */
    protected function isMovedToRoot() {
        if($this->getSourceNode()->getLeft() > $this->getTargetNode()->getLeft() &&
                $this->getSourceNode()->getRight() < $this->getTargetNode()->getRight()) {
            return true;
        } else {
            return false;
        }
    }

    public function getIndexShift() {
        return $this->getSourceNode()->getRight() - $this->getSourceNode()->getLeft() + 1;
    }
}
