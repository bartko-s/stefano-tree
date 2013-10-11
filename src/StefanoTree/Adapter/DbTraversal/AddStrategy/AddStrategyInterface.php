<?php
namespace StefanoTree\Adapter\DbTraversal\AddStrategy;

interface AddStrategyInterface
{
    /**
     * @param int $rootNodeId
     * @return bolean
     */
    public function canAddNewNode($rootNodeId);
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