<?php
namespace StefanoTree\Adapter;

use Zend\Db;
use StefanoDb\Adapter\Adapter as DbAdapter;
use StefanoTree\Adapter\Helper\NodeInfo;
use Exception;

class DbTraversal
    implements AdapterInterface
{
    protected $tableName = null;
    
    protected $idColumnName = null;
    protected $leftColumnName = 'lft';
    protected $rightColumnName = 'rgt';
    protected $levelColumnName = 'level';
    protected $parentIdColumnName = 'parent_id';        
    
    protected $dbAdapter = null;
    
    protected $transactionManager;
    
    protected $defaultDbSelect = null;
    
    /**
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options) {
        $this->setOptions($options);
        
        $errorMessage = array();
        
        if(null == $this->tableName) {
            $errorMessage[] = 'tableName';
        }
        
        if(null == $this->idColumnName) {
            $errorMessage[] = 'idColumnName';
        }
        
        if(null == $this->dbAdapter) {
            $errorMessage[] = 'dbAdapter';
        }
        
        if (count($errorMessage)) {
            throw new Exception(implode(', ', $errorMessage) . ' must be set');
        }
    }
    
    /**
     * @param array $options
     * @return DbTraversal
     */
    public function setOptions($options) {
        foreach($options as $name => $value) {
            $methodName = 'set' . ucfirst($name);
            if(method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
        return $this;
    }
    
    /**
     * @param string $tableName
     * @return DbTraversal
     * @throws \Exception
     */
    public function setTableName($tableName) {
        $tableName = (string) $tableName;
        
        if(0 == strlen($tableName)) {
            throw new Exception('tableName cannot be empty');
        }
        
        $this->tableName = $tableName;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getTableName() {
        return $this->tableName;
    }
    
    /**
     * @param string $idColumnName
     * @return DbTraversal
     * @throws \Exception
     */
    public function setIdColumnName($idColumnName) {
        $idColumnName = (string) $idColumnName;
        
        if(0 == strlen($idColumnName)) {
            throw new Exception('idColumnName cannot be empty');
        }
        
        $this->idColumnName = $idColumnName;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getIdColumnName() {
        return $this->idColumnName;
    }
    
    /**
     * @param string $leftColumnName
     * @return DbTraversal
     * @throws \Exception
     */
    public function setLeftColumnName($leftColumnName) {
        $leftColumnName = (string) $leftColumnName;
        
        if(0 == strlen($leftColumnName)) {
            throw new Exception('leftColumnName cannot be empty');
        }
        
        $this->leftColumnName = $leftColumnName;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getLeftColumnName() {
        return $this->leftColumnName;
    }
    
    /**
     * @param string $rightColumnName
     * @return DbTraversal
     * @throws \Exception
     */
    public function setRightColumnName($rightColumnName) {
        $rightColumnName = (string) $rightColumnName;
        
        if(0 == strlen($rightColumnName)) {
            throw new Exception('rightColumnName cannot be empty');
        }
        
        $this->rightColumnName = $rightColumnName;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getRightColumnName() {
        return $this->rightColumnName;
    }
    
    /**
     * @param string $levelColumnName
     * @return DbTraversal
     * @throws \Exception
     */
    public function setLevelColumnName($levelColumnName) {
        $levelColumnName = (string) $levelColumnName;
        
        if(0 == strlen($levelColumnName)) {
            throw new Exception('levelColumnName cannot be empty');
        }
        
        $this->levelColumnName = $levelColumnName;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getLevelColumnName() {
        return $this->levelColumnName;
    }
    
    /**
     * @param string $parentIdColumnName
     * @return DbTraversal
     * @throws \Exception
     */
    public function setParentIdColumnName($parentIdColumnName) {
        $parentIdColumnName = (string) $parentIdColumnName;
        
        if(0 == strlen($parentIdColumnName)) {
            throw new Exception('parentIdColumnName cannot be empty');
        }
        
        $this->parentIdColumnName = $parentIdColumnName;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getParentIdColumnName() {
        return $this->parentIdColumnName;
    }
    
    /**
     * @param int $nodeId
     * @param array $data
     */
    public function updateNode($nodeId, $data) {
        $disallowedDataKeys = array(
            strtolower($this->getIdColumnName()),
            strtolower($this->getLeftColumnName()),
            strtolower($this->getRightColumnName()),
            strtolower($this->getLevelColumnName()),
            strtolower($this->getParentIdColumnName()),
        );
        
        foreach (array_keys($data) as $key) {                        
            if(array_key_exists(strtolower($key), array_flip($disallowedDataKeys))) {
                unset($data[$key]);
            }
        }
        
        $dbAdapter = $this->getDbAdapter();
        
        $update = new Db\Sql\Update($this->getTableName());
        $update->set($data)
               ->where(array(
                    $this->getIdColumnName() => $nodeId,
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
        $dbAdapter = $this->getDbAdapter();
        $transaction = $dbAdapter->getTransaction();
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($this->getTableName());
            
            if(!$targetNodeInfo = $this->getNodeInfo($targetNodeId)) {
                $transaction->commit();
                $dbLock->unlockTables();
                return false;
            }
        
            if(self::PLACEMENT_BOTTOM == $placement) {
                if(1 == $targetNodeId) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return false;
                }

                $data[$this->getParentIdColumnName()] = $targetNodeInfo->getParentId();
                $data[$this->getLevelColumnName()] = $targetNodeInfo->getLevel();
                $data[$this->getLeftColumnName()] = $targetNodeInfo->getRight() + 1;
                $data[$this->getRightColumnName()] = $targetNodeInfo->getRight() + 2;
                
                $this->moveIndexes($targetNodeInfo->getRight(), 2);                
            } elseif(self::PLACEMENT_TOP == $placement) {
                if(1 == $targetNodeId) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return false;
                }

                $data[$this->getParentIdColumnName()] = $targetNodeInfo->getParentId();
                $data[$this->getLevelColumnName()] = $targetNodeInfo->getLevel();
                $data[$this->getLeftColumnName()] = $targetNodeInfo->getLeft();
                $data[$this->getRightColumnName()] = $targetNodeInfo->getLeft() + 1;
                
                $this->moveIndexes(($targetNodeInfo->getLeft() - 1), 2);
            } elseif(self::PLACEMENT_CHILD_BOTTOM == $placement) {
                $data[$this->getParentIdColumnName()] = $targetNodeInfo->getId();
                $data[$this->getLevelColumnName()] = $targetNodeInfo->getLevel() + 1;
                $data[$this->getLeftColumnName()] = $targetNodeInfo->getRight();
                $data[$this->getRightColumnName()] = $targetNodeInfo->getRight() + 1;
                
                $this->moveIndexes(($targetNodeInfo->getRight() - 1), 2);
            } elseif(self::PLACEMENT_CHILD_TOP == $placement) {
                $data[$this->getParentIdColumnName()] = $targetNodeInfo->getId();
                $data[$this->getLevelColumnName()] = $targetNodeInfo->getLevel() + 1;
                $data[$this->getLeftColumnName()] = $targetNodeInfo->getLeft() + 1;
                $data[$this->getRightColumnName()] = $targetNodeInfo->getLeft() + 2;
                
                $this->moveIndexes($targetNodeInfo->getLeft(), 2);
            } else {
                // @codeCoverageIgnoreStart
                throw new Exception('Unknown placement "' . $placement . '"');
                // @codeCoverageIgnoreEnd
            }
            
            $insert = new Db\Sql\Insert($this->getTableName());
            $insert->values($data);
            $dbAdapter->query($insert->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);
            
            $lastInsertId = $dbAdapter->getDriver()
                                      ->getLastGeneratedValue();
            
            $transaction->commit();
            $dbLock->unlockTables();
        } catch(\Exception $e) {
            $transaction->rollback();
            $dbLock->unlockTables();
            throw $e;
        }
            
        return $lastInsertId;
    }
    
    public function addNodePlacementBottom($targetNodeId, $data = array()) {
        $placement = self::PLACEMENT_BOTTOM;
        return $this->addNode($targetNodeId, $placement, $data);
    }
    
    public function addNodePlacementTop($targetNodeId, $data = array()) {
        $placement = self::PLACEMENT_TOP;
        return $this->addNode($targetNodeId, $placement, $data);
    }
    
    public function addNodePlacementChildBottom($targetNodeId, $data = array()) {
        $placement = self::PLACEMENT_CHILD_BOTTOM;
        return $this->addNode($targetNodeId, $placement, $data);
    }
    
    public function addNodePlacementChildTop($targetNodeId, $data = array()) {
        $placement = self::PLACEMENT_CHILD_TOP;
        return $this->addNode($targetNodeId, $placement, $data);
    }

    /**
     * @param int $sourceNodeId
     * @param int $targetNodeId
     * @param string $placement
     * @return boolean
     * @throws \Exception
     */
    protected function moveNode($sourceNodeId, $targetNodeId, $placement) {
        //su rovnake
        if($sourceNodeId == $targetNodeId) {
            return false;
        }
        
        $dbAdapter = $this->getDbAdapter();
        $transaction = $dbAdapter->getTransaction();
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($this->getTableName());
            
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
            
            $shift = $sourceNodeInfo->getRight() - $sourceNodeInfo->getLeft() + 1;
            $reverseShift = $sourceNodeInfo->getLeft() - $sourceNodeInfo->getRight() - 1;

            if(self::PLACEMENT_BOTTOM == $placement) {
                //cielovy uzol je root
                if(1 == $targetNodeId) {
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

                //upravime rodica
                $this->updateParentId($sourceNodeInfo, $targetNodeInfo->getParentId());
                
                //upravime level
                $this->updateLevels($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(), 
                    $targetNodeInfo->getLevel() - $sourceNodeInfo->getLevel());
                
                //medzera pre presuvanu vetvu
                $this->moveIndexes($targetNodeInfo->getRight(), $shift);
                
                if($sourceNodeInfo->getLeft() > $targetNodeInfo->getLeft() &&
                        $sourceNodeInfo->getRight() < $targetNodeInfo->getRight()) {
                    //presunutie vetvy do medzery
                    $shift2 = $targetNodeInfo->getRight() - $sourceNodeInfo->getLeft() + 1;
                    $this->moveBranch($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(), $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getLeft(), $reverseShift);
                } elseif($targetNodeInfo->getRight() < $sourceNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft() + $shift;
                    $rightTo  = $sourceNodeInfo->getRight() + $shift;
                    $shift2   = $targetNodeInfo->getRight() - $sourceNodeInfo->getLeft() + 1 - $shift;
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes(($sourceNodeInfo->getLeft() + $shift), $reverseShift);                    
                } elseif($sourceNodeInfo->getRight() < $targetNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $shift2 = $targetNodeInfo->getRight() - $sourceNodeInfo->getLeft() + 1;
                    $this->moveBranch($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(), $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getLeft(), $reverseShift);
                } else {
                    // @codeCoverageIgnoreStart
                    throw new Exception('moveBotom - this is impossible');
                    // @codeCoverageIgnoreEnd
                }

            } elseif(self::PLACEMENT_TOP == $placement) {
                //cielovy uzol je root
                if(1 == $targetNodeId) {
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

                //upravime rodica
                $this->updateParentId($sourceNodeInfo, $targetNodeInfo->getParentId());
                
                //upravime level
                $this->updateLevels($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(), 
                        $targetNodeInfo->getLevel() - $sourceNodeInfo->getLevel());
                
                //medzera pre presuvanu vetvu
                $this->moveIndexes(($targetNodeInfo->getLeft() - 1), $shift);
                
                if($sourceNodeInfo->getLeft() > $targetNodeInfo->getLeft() &&
                        $sourceNodeInfo->getRight() < $targetNodeInfo->getRight()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft() + $shift;
                    $rightTo  = $sourceNodeInfo->getRight() + $shift;
                    $shift2   = $targetNodeInfo->getLeft() - $sourceNodeInfo->getRight() - 1;
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                   $this->moveIndexes(($sourceNodeInfo->getRight() + $shift), $reverseShift);                    
                } elseif($targetNodeInfo->getRight() < $sourceNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft() + $shift;
                    $rightTo  = $sourceNodeInfo->getRight() + $shift;
                    $shift2   = $targetNodeInfo->getLeft() - $sourceNodeInfo->getRight() - 1;
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getRight(), $reverseShift);
                } elseif($sourceNodeInfo->getRight() < $targetNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft();
                    $rightTo  = $sourceNodeInfo->getRight();
                    $shift2   = $targetNodeInfo->getLeft() - $sourceNodeInfo->getLeft();
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getLeft(), $reverseShift);
                } else {
                    // @codeCoverageIgnoreStart
                    throw new Exception('moveTop - this is impossible');
                    // @codeCoverageIgnoreEnd
                }

            } elseif(self::PLACEMENT_CHILD_BOTTOM == $placement) {
                //aktualna pozicia je rovnaka ako pozadovana, cize nie je dovod presuvat
                if($sourceNodeInfo->getParentId() == $targetNodeInfo->getId() && 
                        $sourceNodeInfo->getRight() == ($targetNodeInfo->getRight() - 1)) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return true;
                }

                //upravime rodica
                $this->updateParentId($sourceNodeInfo, $targetNodeInfo->getId());
                
                //upravime level
                $this->updateLevels($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(), 
                        $targetNodeInfo->getLevel() - $sourceNodeInfo->getLevel() + 1);
                
                //medzera pre presuvanu vetvu
                $this->moveIndexes(($targetNodeInfo->getRight() - 1), $shift);
                
                if($sourceNodeInfo->getLeft() > $targetNodeInfo->getLeft() &&
                        $sourceNodeInfo->getRight() < $targetNodeInfo->getRight()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft();
                    $rightTo  = $sourceNodeInfo->getRight();
                    $shift2   = $targetNodeInfo->getRight() - $sourceNodeInfo->getLeft();
                    $this->moveBranch($leftFrom, $rightTo, $shift2);
                    
                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getRight(), $reverseShift);
                } elseif($targetNodeInfo->getRight() < $sourceNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft() + $shift;
                    $rightTo  = $sourceNodeInfo->getRight() + $shift;
                    $shift2   = $targetNodeInfo->getRight() - $sourceNodeInfo->getRight() - 1;
                    $this->moveBranch($leftFrom, $rightTo, $shift2);
                    
                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getRight(), $reverseShift);
                } elseif($sourceNodeInfo->getRight() < $targetNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft();
                    $rightTo  = $sourceNodeInfo->getRight();
                    $shift2   = $targetNodeInfo->getRight() - $sourceNodeInfo->getLeft();
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getRight(), $reverseShift);
                } else {
                    // @codeCoverageIgnoreStart
                    throw new Exception('moveChildBottom - this is impossible');
                    // @codeCoverageIgnoreEnd
                }
            } elseif(self::PLACEMENT_CHILD_TOP == $placement) {
                //aktualna pozicia je rovnaka ako pozadovana, cize nie je dovod presuvat
                if($sourceNodeInfo->getParentId() == $targetNodeInfo->getId() &&
                        $targetNodeInfo->getLeft() == ($sourceNodeInfo->getLeft() - 1)) {
                    $transaction->commit();
                    $dbLock->unlockTables();
                    return true;
                }
                
                //upravime rodica
                $this->updateParentId($sourceNodeInfo, $targetNodeInfo->getId());
                
                //upravime level
                $this->updateLevels($sourceNodeInfo->getLeft(), $sourceNodeInfo->getRight(), 
                        $targetNodeInfo->getLevel() - $sourceNodeInfo->getLevel() + 1);
                
                //medzera pre presuvanu vetvu
                $this->moveIndexes($targetNodeInfo->getLeft(), $shift);
                
                if($sourceNodeInfo->getLeft() > $targetNodeInfo->getLeft() &&
                        $sourceNodeInfo->getRight() < $targetNodeInfo->getRight()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft() + $shift;
                    $rightTo  = $sourceNodeInfo->getRight() + $shift;
                    $shift2   = $targetNodeInfo->getLeft() - $sourceNodeInfo->getRight();
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getRight(), $reverseShift);
                } elseif($targetNodeInfo->getRight() < $sourceNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft() + $shift;
                    $rightTo  = $sourceNodeInfo->getRight() + $shift;
                    $shift2   = $targetNodeInfo->getLeft() - $sourceNodeInfo->getRight();
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getRight(), $reverseShift);
                } elseif($sourceNodeInfo->getRight() < $targetNodeInfo->getLeft()) {
                    //presunutie vetvy do medzery
                    $leftFrom = $sourceNodeInfo->getLeft();
                    $rightTo  = $sourceNodeInfo->getRight();
                    $shift2   = $targetNodeInfo->getLeft() - $sourceNodeInfo->getLeft() + 1;
                    $this->moveBranch($leftFrom, $rightTo, $shift2);

                    //zaplatanie medzeri po presunutom uzle
                    $this->moveIndexes($sourceNodeInfo->getRight(), $reverseShift);
                } else {
                    // @codeCoverageIgnoreStart
                    throw new Exception('moveChildTop - this is impossible');
                    // @codeCoverageIgnoreEnd
                }
            } else {
                // @codeCoverageIgnoreStart
                throw new Exception('Unknown placement "' . $placement . '"');
                // @codeCoverageIgnoreEnd
            }

            $transaction->commit();
            $dbLock->unlockTables();
        } catch(\Exception $e) {
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
    
    public function deleteBranch($nodeId) {
        if(1 == $nodeId) {
            return false;
        }
        
        $dbAdapter = $this->getDbAdapter();
        $transaction = $dbAdapter->getTransaction();
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($this->getTableName());
            
            // neexistuje
            if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
                $transaction->commit();
                $dbLock->unlockTables();
                return false;
            }
            
            $delete = new Db\Sql\Delete($this->getTableName());
            $delete->where
                   ->greaterThanOrEqualTo($this->getLeftColumnName(), $nodeInfo->getLeft())
                   ->AND
                   ->lessThanOrEqualTo($this->getRightColumnName(), $nodeInfo->getRight());
            
            $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()), 
                DbAdapter::QUERY_MODE_EXECUTE);
            
            $shift = $nodeInfo->getLeft() - $nodeInfo->getRight() - 1;            
            $this->moveIndexes($nodeInfo->getLeft(), $shift);

            $transaction->commit();
            $dbLock->unlockTables();
        } catch (\Exception $e) {
            $transaction->rollback();
            $dbLock->unlockTables();
            throw $e;
        }
        
        return true;
    }
    
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false) {
        $startLevel = (int) $startLevel;
        
        // neexistuje
        if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }
        
        $dbAdapter = $this->getDbAdapter();
        
        $select = $this->getDefaultDbSelect();
        $select->where
               ->lessThanOrEqualTo($this->getLeftColumnName(), $nodeInfo->getLeft())
               ->AND
               ->greaterThanOrEqualTo($this->getRightColumnName(), $nodeInfo->getRight());
        
        $select->order($this->getLeftColumnName() . ' ASC');
        
        if(0 < $startLevel) {
            $select->where
                   ->greaterThanOrEqualTo($this->getLevelColumnName(), $startLevel);
        }
        
        if(true == $excludeLastNode) {
            $select->where
                   ->lessThan($this->getLevelColumnName(), $nodeInfo->getLevel());            
        }
        
        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
        
        return $result->toArray();
    }
    
    /**
     * Clear all data except root node
     * 
     * @return DbTraversal
     * @throws \Exception
     */
    public function clear() {
        $dbAdapter = $this->getDbAdapter();
        
        $transaction = $dbAdapter->getTransaction();        
        $dbLock = $dbAdapter->getLockAdapter();
        
        try {
            $transaction->begin();
            $dbLock->lockTables($this->getTableName());
            
            $delete = new Db\Sql\Delete;
            $delete->from($this->getTableName())
                   ->where
                   ->notEqualTo($this->getIdColumnName(), 1);
            $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);
            
            $update = new Db\Sql\Update();
            $update->table($this->getTableName())
                   ->set(array(
                        $this->getParentIdColumnName() => 0,
                        $this->getLeftColumnName() => 1,
                        $this->getRightColumnName() => 2,
                        $this->getLevelColumnName() => 0,
                   ));
            $dbAdapter->query($update->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);       
            
            $transaction->commit();
            $dbLock->unlockTables();
        } catch (\Exception $e) {
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
        $result = $this->getNode($nodeId);

        if(null == $result) {
            $result = null;
        } else {
            $params = array(
                'id'        => $result[$this->getIdColumnName()],
                'parentId'  => $result[$this->getParentIdColumnName()],
                'level'     => $result[$this->getLevelColumnName()],
                'left'      => $result[$this->getLeftColumnName()],
                'right'     => $result[$this->getRightColumnName()],
            );

            $result = new NodeInfo($params);
        }
            
        return $result;
    }    
    
    public function getNode($nodeId) {
        $nodeId = (int) $nodeId;
        
        $dbAdapter = $this->getDbAdapter();
        
        $select = $this->getDefaultDbSelect()
                       ->where(array($this->idColumnName =>  $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()), 
                DbAdapter::QUERY_MODE_EXECUTE);
        
        $array = $result->toArray();
        
        if(0 < count($array)) {
            return $array[0];
        }  
    }
        
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranche = null) {
        if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($this->getLeftColumnName() . ' ASC');
        
        
        if(0 != $startLevel) {
            $level = $nodeInfo->getLevel() + (int) $startLevel;
            $select->where
                   ->greaterThanOrEqualTo($this->getLevelColumnName(), $level);            
        }
        
        if(null != $levels) {
            $endLevel = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
            $select->where
                   ->lessThan($this->getLevelColumnName(), $endLevel);            
        }
        
        if(null != $excludeBranche && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranche))) {
            $select->where
                   ->NEST
                   ->between($this->getLeftColumnName(),
                        $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
                   ->OR
                   ->between($this->getLeftColumnName(),
                        $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight())
                   ->UNNEST
                   ->AND
                   ->NEST
                   ->between($this->getRightColumnName(),
                        $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight())
                   ->OR
                   ->between($this->getRightColumnName(),
                        $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
                   ->UNNEST;
        } else {
            $select->where
                   ->greaterThanOrEqualTo($this->getLeftColumnName(), $nodeInfo->getLeft())
                   ->AND
                   ->lessThanOrEqualTo($this->getRightColumnName(), $nodeInfo->getRight());            
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
        if(null == $this->defaultDbSelect) {
            $this->defaultDbSelect = new Db\Sql\Select($this->getTableName());
        }

        $dbSelect = clone $this->defaultDbSelect;
        
        $transaction = $this->getDbAdapter()
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
     * @return DbTraversal
     */
    public function setDefaultDbSelect(\Zend\Db\Sql\Select $dbSelect) {
        $this->defaultDbSelect = $dbSelect;
        return $this;
    }

    /**
     * @return DbAdapter
     */
    public function getDbAdapter() {
        return $this->dbAdapter;
    }   
    
    /**
     * @param DbAdapter $dbAdapter
     * @return DbTraversal
     */
    public function setDbAdapter(DbAdapter $dbAdapter) {
        $this->dbAdapter = $dbAdapter;
        return $this;
    }
    
    /**
     * @param NodeInfo $nodeInfo
     * @param int $newParentId
     */
    protected function updateParentId(NodeInfo $nodeInfo, $newParentId) {
        if($newParentId == $nodeInfo->getParentId()) {
            return;
        }
        
        $dbAdapter = $this->getDbAdapter();
        
        $update = new Db\Sql\Update($this->getTableName());
        $update->set(array(
                    $this->getParentIdColumnName() => $newParentId,
               ))
               ->where(array(
                   $this->getIdColumnName() => $nodeInfo->getId(),
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
        if(0 == $shift) {
            return;
        }
        
        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();
        
        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($this->getTableName())
            . ' SET '
                . $dbPlatform->quoteIdentifier($this->getLevelColumnName()) . ' = ' 
                    . $dbPlatform->quoteIdentifier($this->getLevelColumnName()) . ' + :shift'
            . ' WHERE '
                . $dbPlatform->quoteIdentifier($this->getLeftColumnName()) . ' >= :leftFrom'
                . ' AND ' . $dbPlatform->quoteIdentifier($this->getRightColumnName()) . ' <= :rightTo';

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
        if(0 == $shift) {
            return;
        }
        
        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();        
        $transaction = $dbAdapter->getTransaction();
        
        try {
            $sqls = array();
            $sqls[] = 'UPDATE ' . $dbPlatform->quoteIdentifier($this->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($this->getLeftColumnName()) . ' = ' 
                        . $dbPlatform->quoteIdentifier($this->getLeftColumnName()) . ' + :shift'
                . ' WHERE '
                    . $dbPlatform->quoteIdentifier($this->getLeftColumnName()) . ' > :fromIndex';

            $sqls[] = 'UPDATE ' . $dbPlatform->quoteIdentifier($this->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($this->getRightColumnName()) . ' = ' 
                        . $dbPlatform->quoteIdentifier($this->getRightColumnName()) . ' + :shift'
                . ' WHERE '                            
                    . $dbPlatform->quoteIdentifier($this->getRightColumnName()) . ' > :fromIndex';

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
        } catch(\Exception $e) {
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
        
        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();
        
        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($this->getTableName())
            . ' SET '
                . $dbPlatform->quoteIdentifier($this->getLeftColumnName()) . ' = ' 
                    . $dbPlatform->quoteIdentifier($this->getLeftColumnName()) . ' + :shift, '
                . $dbPlatform->quoteIdentifier($this->getRightColumnName()) . ' = ' 
                    . $dbPlatform->quoteIdentifier($this->getRightColumnName()) . ' + :shift'
            . ' WHERE '
                . $dbPlatform->quoteIdentifier($this->getLeftColumnName()) . ' >= :leftFrom'
                . ' AND ' . $dbPlatform->quoteIdentifier($this->getRightColumnName()) . ' <= :rightTo';
        
        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftFrom,
            ':rightTo' => $rightTo,
        );
        
        $dbAdapter->query($sql)
                  ->execute($binds);
    }
}
