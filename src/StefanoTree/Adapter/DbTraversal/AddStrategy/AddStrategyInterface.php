<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

interface AddStrategyInterface
{
    /**
     * @return int
     */
    public function moveIndexesFromIndex();

    /**
     * @return int
     */
    public function newParentId();

    /**
     * @return int
     */
    public function newLevel();

    /**
     * @return int
     */
    public function newLeftIndex();

    /**
     * @return int
     */
    public function newRightIndex();
}