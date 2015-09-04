<?php
namespace StefanoTree\NestedSet\MoveStrategy;

interface MoveStrategyInterface
{
    /**
     * @param int $rootNodeId
     * @return boolean
     */
    public function canMoveBranch($rootNodeId);

    /**
     * @return boolean
     */
    public function isSourceNodeAtRequiredPosition();

    /**
     * @return int
     */
    public function getNewParentId();

    /**
     * @return int
     */
    public function getLevelShift();

    /**
     * hole for moved branch
     *
     * @return int
     */
    public function getIndexShift();

    /**
     * @return int
     */
    public function getSourceNodeIndexShift();

    /**
     * @return int
     */
    public function getHoleLeftIndex();

    /**
     * @return int
     */
    public function getHoleRightIndex();

    /**
     * @return int
     */
    public function fixHoleFromIndex();

    /**
     * @return int
     */
    public function makeHoleFromIndex();
}