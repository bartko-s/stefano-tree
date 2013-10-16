<?php
namespace StefanoTree\Adapter\DbTraversal\MoveStrategy;

use StefanoTree\Adapter\DbTraversal\NodeInfo;
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

    public function canMoveBranche($rootNodeId) {
        return ($this->isTargetNodeInsideSourceBranche()) ?
            false : true;
    }

    protected function isTargetNodeInsideSourceBranche() {
        $targetNode = $this->getTargetNode();
        $sourceNode = $this->getSourceNode();

        return ($targetNode->getLeft() > $sourceNode->getLeft() &&
                $targetNode->getRight() < $sourceNode->getRight()) ?
            true : false;
    }

    protected function isTargetNodeRootNode($rootNodeId) {
        return ($rootNodeId == $this->getTargetNode()->getId()) ?
            true : false;
    }
}
