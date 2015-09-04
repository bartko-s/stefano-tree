<?php
namespace StefanoTree\NestedSet\MoveStrategy;

use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\MoveStrategy\MoveStrategyInterface;

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
     * @return boolean
     */
    protected function isMovedUp() {
        return ($this->getTargetNode()->getRight() < $this->getSourceNode()->getLeft()) ?
            true : false;
    }

    /**
     * @return boolean
     */
    protected function isMovedDown() {
        return ($this->getSourceNode()->getRight() < $this->getTargetNode()->getLeft()) ?
            true : false;
    }

    /**
     * @return boolean
     */
    protected function isMovedToRoot() {
        return ($this->getSourceNode()->getLeft() > $this->getTargetNode()->getLeft() &&
                    $this->getSourceNode()->getRight() < $this->getTargetNode()->getRight()) ?
            true : false;
    }

    public function getIndexShift() {
        return $this->getSourceNode()->getRight() - $this->getSourceNode()->getLeft() + 1;
    }

    public function canMoveBranch($rootNodeId) {
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
