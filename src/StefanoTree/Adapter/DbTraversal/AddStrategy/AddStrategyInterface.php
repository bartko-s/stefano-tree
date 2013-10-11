<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

use StefanoTree\Adapter\Helper\NodeInfo;

interface AddStrategyInterface
{
    /**
     * @return NodeInfo
     */
    public function calculateNewNode();

    /**
     * @return int
     */
    public function moveIndexesFromIndex();
}