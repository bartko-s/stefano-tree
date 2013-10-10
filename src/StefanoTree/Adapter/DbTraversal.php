<?php
namespace StefanoTree\Adapter;

use Zend\Db;
use StefanoDb\Adapter\Adapter as DbAdapter;
use StefanoTree\Adapter\Helper\NodeInfo;
use Exception;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\Adapter\DbTraversal\Options;
use StefanoTree\Adapter\DbTraversal\AddStrategy;
use StefanoTree\Adapter\DbTraversal\AddStrategy\AddStrategyInterface;
use StefanoTree\Adapter\DbTraversal\MoveStrategy;
use StefanoTree\Adapter\DbTraversal\MoveStrategy\MoveStrategyInterface;

class DbTraversal
    implements AdapterInterface
{
    private $options = null;

    private $defaultDbSelect = null;

    /**
     * dbAdapter (required)
     * tableName (required)
     * idColumnName (required)
     * leftColumnName (optional) default "lft"
     * rightColumnName (optional) default "rgt"
     * levelColumnName (optional) default "level"
     * parentIdColumnName (optional) default "parent_id"
     *
     * @param array|Options $options
     * @throws InvalidArgumentException
     */
    public function __construct($options) {
        if(is_array($options)) {
            $this->options = new Options($options);
        } elseif($this->options instanceof Options) {
            $this->options = $options;
        } else {
            throw new InvalidArgumentException('Options must be array or Options object');
        }
    }

    /**
     * @return Options
     */
    protected function getOptions() {
        return $this->options;
    }

    /**
     * Test if node is root node
     *
     * @param int $nodeId
     * @return boolean
     */
    private function isRoot($nodeId) {
        if(1 == $nodeId) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Data cannot contain keys like idColomnName, levelColumnName, ...
     *
     * @param array $data
     * @return array
     */
    private function cleanData(array $data) {
        $options = $this->getOptions();

        $disallowedDataKeys = array(
            $options->getIdColumnName(),
            $options->getLeftColumnName(),
            $options->getRightColumnName(),
            $options->getLevelColumnName(),
            $options->getParentIdColumnName(),
        );

        return array_diff_key($data, array_flip($disallowedDataKeys));
    }
    
    /**
     * @param int $nodeId
     * @param array $data
     */
    public function updateNode($nodeId, $data) {
        $options = $this->getOptions();

        $dbAdapter = $options->getDbAdapter();
        
        $update = new Db\Sql\Update($options->getTableName());
        $update->set($this->cleanData($data))
               ->where(array(
                    $options->getIdColumnName() => $nodeId,
               ));
        
        $dbAdapter->query($update->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);
    }
    
    /**
     * @param int $targetNodeId
     * @param string $placement
     * @param array $data
     * @return int|false Id of new created node. False if node has not been created
     * @throws Exception
     */
    protected function addNode($targetNodeId, $placement, $data = array()) {
        $options = $this->getOptions();
        
        $dbAdapter = $options->getDbAdapter();
        $transaction = $dbAdapter->getTransaction();
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($options->getTableName());
            
            if(!$targetNodeInfo = $this->getNodeInfo($targetNodeId)) {
                $transaction->commit();
                $dbLock->unlockTables();
                return false;
            }
            
            if(self::PLACEMENT_BOTTOM == $placement || self::PLACEMENT_TOP == $placement) {
                if($this->isRoot($targetNodeId)) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return false;
                }      
            }

            $addStrategy = $this->getAddStrategy($placement);

            $this->moveIndexes($addStrategy->moveIndexesFromIndex($targetNodeInfo), 2);

            $newNode = $addStrategy->calculateNewNode($targetNodeInfo);

            $data[$options->getParentIdColumnName()] = $newNode->getParentId();
            $data[$options->getLevelColumnName()] = $newNode->getLevel();
            $data[$options->getLeftColumnName()] = $newNode->getLeft();
            $data[$options->getRightColumnName()] = $newNode->getRight();

            $insert = new Db\Sql\Insert($options->getTableName());
            $insert->values($data);
            $dbAdapter->query($insert->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);
            
            $lastGeneratedValue = $dbAdapter->getDriver()
                                            ->getLastGeneratedValue();
            
            $transaction->commit();
            $dbLock->unlockTables();
        } catch(Exception $e) {
            $transaction->rollback();
            $dbLock->unlockTables();
            throw $e;
        }
            
        return $lastGeneratedValue;
    }

    /**
     * @param string $placement
     * @return AddStrategyInterface
     * @throws InvalidArgumentException
     */
    private function getAddStrategy($placement) {
        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new AddStrategy\Bottom();
            case self::PLACEMENT_TOP:
                return new AddStrategy\Top();
            case self::PLACEMENT_CHILD_BOTTOM:
                return new AddStrategy\ChildBottom();
            case self::PLACEMENT_CHILD_TOP:
                return new AddStrategy\ChildTop();
            default:
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException('Unknown placement "' . $placement . '"');
                // @codeCoverageIgnoreEnd
        }
    }

    public function addNodePlacementBottom($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_BOTTOM, $data);
    }
    
    public function addNodePlacementTop($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_TOP, $data);
    }
    
    public function addNodePlacementChildBottom($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_CHILD_BOTTOM, $data);
    }
    
    public function addNodePlacementChildTop($targetNodeId, $data = array()) {
        return $this->addNode($targetNodeId, self::PLACEMENT_CHILD_TOP, $data);
    }

    /**
     * @param int $sourceNodeId
     * @param int $targetNodeId
     * @param string $placement
     * @return boolean
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function moveNode($sourceNodeId, $targetNodeId, $placement) {
        $options = $this->getOptions();

        //su rovnake
        if($sourceNodeId == $targetNodeId) {
            return false;
        }
        
        $dbAdapter = $options->getDbAdapter();
        $transaction = $dbAdapter->getTransaction();
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($options->getTableName());
            
            //neexistuje
            if(!$sourceNodeInfo = $this->getNodeInfo($sourceNodeId)) {
                $transaction->commit();
                $dbLock->unlockTables();
                return false;
            }
            //neexistuje
            if(!$targetNodeInfo = $this->getNodeInfo($targetNodeId)) {
                $transaction->commit();
                $dbLock->unlockTables();
                return false;
            }
            //cielovy uzol lezi v zdrojovej vetve
            if($targetNodeInfo->getLeft() > $sourceNodeInfo->getLeft() &&
                    $targetNodeInfo->getRight() < $sourceNodeInfo->getRight()) {
                $transaction->commit();
                $dbLock->unlockTables();
                return false;
            }
            
            if(self::PLACEMENT_BOTTOM == $placement) {
                //cielovy uzol je root
                if($this->isRoot($targetNodeId)) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return false;
                }
                //aktualna pozicia je rovnaka ako pozadovana, cize nie je dovod presuvat
                if($targetNodeInfo->getRight() == ($sourceNodeInfo->getLeft() - 1) &&
                        $targetNodeInfo->getParentId() == $sourceNodeInfo->getParentId()) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return true;
                }
            } elseif(self::PLACEMENT_TOP == $placement) {
                //cielovy uzol je root
                if($this->isRoot($targetNodeId)) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return false;
                }

                //aktualna pozicia je rovnaka ako pozadovana, cize nie je dovod presuvat
                if($targetNodeInfo->getLeft() == ($sourceNodeInfo->getRight() + 1) &&
                        $targetNodeInfo->getParentId() == $sourceNodeInfo->getParentId()) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return true;
                }
            } elseif(self::PLACEMENT_CHILD_BOTTOM == $placement) {
                //aktualna pozicia je rovnaka ako pozadovana, cize nie je dovod presuvat
                if($sourceNodeInfo->getParentId() == $targetNodeInfo->getId() && 
                        $sourceNodeInfo->getRight() == ($targetNodeInfo->getRight() - 1)) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return true;
                }
            } elseif(self::PLACEMENT_CHILD_TOP == $placement) {
                //aktualna pozicia je rovnaka ako pozadovana, cize nie je dovod presuvat
                if($sourceNodeInfo->getParentId() == $targetNodeInfo->getId() &&
                        $targetNodeInfo->getLeft() == ($sourceNodeInfo->getLeft() - 1)) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return true;
                }
            }

            $moveStrategy = $this->getMoveStrategy($sourceNodeInfo, $targetNodeInfo, $placement);

            $reverseShift = $moveStrategy->getIndexShift() * -1;

            //upravime rodica
            $this->updateParentId($sourceNodeInfo, $moveStrategy->getNewParentId());

            //upravime level
            $this->updateLevels($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(),
                    $moveStrategy->getLevelShift());

            //medzera pre presuvanu vetvu
            $this->moveIndexes($moveStrategy->makeHoleFromIndex(),
                $moveStrategy->getIndexShift());

            //presunutie vetvy do medzery
            $this->moveBranch($moveStrategy->getHoleLeftIndex(),
                $moveStrategy->getHoleRightIndex(), $moveStrategy->getSourceNodeIndexShift());

            //zaplatanie medzeri po presunutom uzle
            $this->moveIndexes($moveStrategy->fixHoleFromIndex(), $reverseShift);

            $transaction->commit();
            $dbLock->unlockTables();
        } catch(Exception $e) {
            $transaction->rollback();
            $dbLock->unlockTables();
            throw $e;
        }
        
        return true;
    }
    
    public function moveNodePlacementBottom($sourceNodeId, $targetNodeId) {
        $placement = self::PLACEMENT_BOTTOM;
        return $this->moveNode($sourceNodeId, $targetNodeId, $placement);
    }

    public function moveNodePlacementTop($sourceNodeId, $targetNodeId) {
        $placement = self::PLACEMENT_TOP;
        return $this->moveNode($sourceNodeId, $targetNodeId, $placement);
    }

    public function moveNodePlacementChildBottom($sourceNodeId, $targetNodeId) {
        $placement = self::PLACEMENT_CHILD_BOTTOM;
        return $this->moveNode($sourceNodeId, $targetNodeId, $placement);
    }    
    
    public function moveNodePlacementChildTop($sourceNodeId, $targetNodeId) {
        $placement = self::PLACEMENT_CHILD_TOP;
        return $this->moveNode($sourceNodeId, $targetNodeId, $placement);
    }

    /**
     * @param NodeInfo $sourceNode
     * @param NodeInfo $targetNode
     * @param string $placement
     * @return MoveStrategyInterface
     * @throws InvalidArgumentException
     */
    private function getMoveStrategy(NodeInfo $sourceNode, NodeInfo $targetNode, $placement) {
        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new MoveStrategy\Bottom($sourceNode, $targetNode);
            case self::PLACEMENT_TOP:
                return new MoveStrategy\Top($sourceNode, $targetNode);
            case self::PLACEMENT_CHILD_BOTTOM:
                return new MoveStrategy\ChildBottom($sourceNode, $targetNode);
            case self::PLACEMENT_CHILD_TOP:
                return new MoveStrategy\ChildTop($sourceNode, $targetNode);
            default:
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException('Unknown placement "' . $placement . '"');
                // @codeCoverageIgnoreEnd
        }
    }
    
    public function deleteBranch($nodeId) {
        if($this->isRoot($nodeId)) {
            return false;
        }

        $options = $this->getOptions();
        
        $dbAdapter = $options->getDbAdapter();
        $transaction = $dbAdapter->getTransaction();
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($options->getTableName());
            
            // neexistuje
            if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
                $transaction->commit();
                $dbLock->unlockTables();
                return false;
            }
            
            $delete = new Db\Sql\Delete($options->getTableName());
            $delete->where
                   ->greaterThanOrEqualTo($options->getLeftColumnName(), $nodeInfo->getLeft())
                   ->AND
                   ->lessThanOrEqualTo($options->getRightColumnName(), $nodeInfo->getRight());
            
            $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()), 
                DbAdapter::QUERY_MODE_EXECUTE);
            
            $shift = $nodeInfo->getLeft() - $nodeInfo->getRight() - 1;            
            $this->moveIndexes($nodeInfo->getLeft(), $shift);

            $transaction->commit();
            $dbLock->unlockTables();
        } catch (Exception $e) {
            $transaction->rollback();
            $dbLock->unlockTables();
            throw $e;
        }
        
        return true;
    }
    
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false) {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;
        
        // neexistuje
        if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }
        
        $dbAdapter = $options->getDbAdapter();
        
        $select = $this->getDefaultDbSelect();
        $select->where
               ->lessThanOrEqualTo($options->getLeftColumnName(), $nodeInfo->getLeft())
               ->AND
               ->greaterThanOrEqualTo($options->getRightColumnName(), $nodeInfo->getRight());
        
        $select->order($options->getLeftColumnName() . ' ASC');
        
        if(0 < $startLevel) {
            $select->where
                   ->greaterThanOrEqualTo($options->getLevelColumnName(), $startLevel);
        }
        
        if(true == $excludeLastNode) {
            $select->where
                   ->lessThan($options->getLevelColumnName(), $nodeInfo->getLevel());
        }
        
        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
        
        return $result->toArray();
    }
    
    /**
     * Clear all data except root node
     * 
     * @return this
     * @throws \Exception
     */
    public function clear() {
        $options = $this->getOptions();
        $dbAdapter = $options->getDbAdapter();
        
        $transaction = $dbAdapter->getTransaction();        
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($options->getTableName());
            
            $delete = new Db\Sql\Delete;
            $delete->from($options->getTableName())
                   ->where
                   ->notEqualTo($options->getIdColumnName(), 1);
            $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);
            
            $update = new Db\Sql\Update();
            $update->table($options->getTableName())
                   ->set(array(
                        $options->getParentIdColumnName() => 0,
                        $options->getLeftColumnName() => 1,
                        $options->getRightColumnName() => 2,
                        $options->getLevelColumnName() => 0,
                   ));
            $dbAdapter->query($update->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);       
            
            $transaction->commit();
            $dbLock->unlockTables();
        } catch (Exception $e) {
            $transaction->rollback();
            $dbLock->unlockTables();
            throw $e;
        }
        
        return $this;
    }    
    
    /**
     * @param int $id
     * @return NodeInfo|null
     */
    public function getNodeInfo($nodeId) {
        $options = $this->getOptions();
        $result = $this->getNode($nodeId);

        if(null == $result) {
            $result = null;
        } else {
            $params = array(
                'id'        => $result[$options->getIdColumnName()],
                'parentId'  => $result[$options->getParentIdColumnName()],
                'level'     => $result[$options->getLevelColumnName()],
                'left'      => $result[$options->getLeftColumnName()],
                'right'     => $result[$options->getRightColumnName()],
            );

            $result = new NodeInfo($params);
        }
            
        return $result;
    }    
    
    public function getNode($nodeId) {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;
        
        $dbAdapter = $options->getDbAdapter();
        
        $select = $this->getDefaultDbSelect()
                       ->where(array($options->getIdColumnName() =>  $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()), 
                DbAdapter::QUERY_MODE_EXECUTE);
        
        $array = $result->toArray();
        
        if(0 < count($array)) {
            return $array[0];
        }  
    }
        
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranche = null) {
        $options = $this->getOptions();

        if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }

        $dbAdapter = $options->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($options->getLeftColumnName() . ' ASC');
        
        
        if(0 != $startLevel) {
            $level = $nodeInfo->getLevel() + (int) $startLevel;
            $select->where
                   ->greaterThanOrEqualTo($options->getLevelColumnName(), $level);
        }
        
        if(null != $levels) {
            $endLevel = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
            $select->where
                   ->lessThan($options->getLevelColumnName(), $endLevel);
        }
        
        if(null != $excludeBranche && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranche))) {
            $select->where
                   ->NEST
                   ->between($options->getLeftColumnName(),
                        $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
                   ->OR
                   ->between($options->getLeftColumnName(),
                        $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight())
                   ->UNNEST
                   ->AND
                   ->NEST
                   ->between($options->getRightColumnName(),
                        $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight())
                   ->OR
                   ->between($options->getRightColumnName(),
                        $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
                   ->UNNEST;
        } else {
            $select->where
                   ->greaterThanOrEqualTo($options->getLeftColumnName(), $nodeInfo->getLeft())
                   ->AND
                   ->lessThanOrEqualTo($options->getRightColumnName(), $nodeInfo->getRight());
        }
        
        $result =  $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
        
        $resultArray = $result->toArray();
        
        if(0 < count($resultArray)) {
            return $resultArray;
        }
    }    
    
    public function getChildren($nodeId) {
        return $this->getDescendants($nodeId, 1, 1);
    }
    
    /**
     * Return clone of default db select
     * @todo select for update if transaction is open
     * @return \Zend\Db\Sql\Select
     */
    public function getDefaultDbSelect() {
        $options = $this->getOptions();

        if(null == $this->defaultDbSelect) {
            $this->defaultDbSelect = new Db\Sql\Select($options->getTableName());
        }

        $dbSelect = clone $this->defaultDbSelect;
        
        $transaction = $options->getDbAdapter()
                            ->getTransaction();
        if($transaction->isInTransaction()) {
            /*$dbSelect->forUpdate();
             * but this is not implemented yet
             * https://github.com/zendframework/zf2/issues/3012
             */
        }
        
        return $dbSelect;
    }
    
    /**    
     * @param \Zend\Db\Sql\Select $dbSelect
     * @return this
     */
    public function setDefaultDbSelect(\Zend\Db\Sql\Select $dbSelect) {
        $this->defaultDbSelect = $dbSelect;
        return $this;
    }
    
    /**
     * @param NodeInfo $nodeInfo
     * @param int $newParentId
     */
    protected function updateParentId(NodeInfo $nodeInfo, $newParentId) {
        $options = $this->getOptions();
        
        if($newParentId == $nodeInfo->getParentId()) {
            return;
        }
        
        $dbAdapter = $options->getDbAdapter();
        
        $update = new Db\Sql\Update($options->getTableName());
        $update->set(array(
                    $options->getParentIdColumnName() => $newParentId,
               ))
               ->where(array(
                   $options->getIdColumnName() => $nodeInfo->getId(),
               ));
        
        $dbAdapter->query($update->getSqlString($dbAdapter->getPlatform()), 
            DbAdapter::QUERY_MODE_EXECUTE);
    }
    
    /**
     * @param int $leftFrom from left index
     * @param int $rightTo to right index
     * @param int $shift shift
     */
    protected function updateLevels($leftFrom, $rightTo, $shift) {
        $options = $this->getOptions();

        if(0 == $shift) {
            return;
        }
        
        $dbAdapter = $options->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();
        
        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
            . ' SET '
                . $dbPlatform->quoteIdentifier($options->getLevelColumnName()) . ' = '
                    . $dbPlatform->quoteIdentifier($options->getLevelColumnName()) . ' + :shift'
            . ' WHERE '
                . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' >= :leftFrom'
                . ' AND ' . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' <= :rightTo';

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftFrom,
            ':rightTo' => $rightTo,
        );
        
        $dbAdapter->query($sql)
                  ->execute($binds);
    }
    
    /**
     * Create or fix hole in tree
     * 
     * @param int $fromIndex left and right index from
     * @param int $shift 
     * @throws \Exception
     */
    protected function moveIndexes($fromIndex, $shift) {
        $options = $this->getOptions();

        if(0 == $shift) {
            return;
        }
        
        $dbAdapter = $options->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();        
        $transaction = $dbAdapter->getTransaction();
        
        try {
            $sqls = array();
            $sqls[] = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' = '
                        . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' + :shift'
                . ' WHERE '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' > :fromIndex';

            $sqls[] = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' = '
                        . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
                . ' WHERE '                            
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' > :fromIndex';

            $binds = array(
                ':shift' => $shift,
                ':fromIndex' => $fromIndex,
            );
        
            $transaction->begin();
            
            foreach($sqls as $singleSql) {
                $dbAdapter->query($singleSql)
                          ->execute($binds);
            }
            
            $transaction->commit();
        } catch(Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }
    
    /**
     * Move branch
     * 
     * @param int $leftFrom from left index
     * @param int $rightTo to right index
     * @param int $shift
     */
    protected function moveBranch($leftFrom, $rightTo, $shift) {
        if(0 == $shift) {
            return;
        }

        $options = $this->getOptions();
        
        $dbAdapter = $options->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();
        
        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
            . ' SET '
                . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' = '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' + :shift, '
                . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' = '
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
            . ' WHERE '
                . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' >= :leftFrom'
                . ' AND ' . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' <= :rightTo';
        
        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftFrom,
            ':rightTo' => $rightTo,
        );
        
        $dbAdapter->query($sql)
                  ->execute($binds);
    }
}