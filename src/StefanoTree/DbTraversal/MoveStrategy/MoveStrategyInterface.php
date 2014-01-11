<?php
namespace StefanoTree\DbTraversal\MoveStrategy;

interface MoveStrategyInterface
{
    /**
     * @param int $rootNodeId
     * @return bolean
     */
    public function canMoveBranche($rootNodeId);

    /**
     * @return bolean
     */
    public function isSourceNodeAtRequiredPossition();

    /**
     * @return int
     */
    public function getNewParentId();

    /**
     * @return int
     */
    public function getLevelShift();

    /**
     * hole for moved branche
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