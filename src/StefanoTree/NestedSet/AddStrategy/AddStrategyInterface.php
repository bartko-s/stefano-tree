<?php
namespace StefanoTree\NestedSet\AddStrategy;

interface AddStrategyInterface
{
    /**
     * @param int $rootNodeId
     * @return boolean
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
