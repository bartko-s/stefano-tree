<?php
namespace StefanoTree\NestedSet\AddStrategy;

interface AddStrategyInterface
{
    /**
     * @return boolean
     */
    public function canAddNewNode();
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
