<?php
namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\Options;
use StefanoDb\Adapter\Adapter as DbAdapter;
use Zend\Db;
use StefanoTree\NestedSet\NodeInfo;
use StefanoLockTable\Factory as LockSqlBuilderFactory;
use StefanoLockTable\Adapter\AdapterInterface as LockSqlBuilderInterface;

class Zend2DbAdapter
    implements AdapterInterface
{
    private $options;

    private $dbAdapter;

    private $defaultDbSelect = null;

    private $lockSqlBuilder;

    public function __construct(Options $options, DbAdapter $dbAdapter)
    {
        $this->options = $options;
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @return Options
     */
    private function getOptions()
    {
        return $this->options;
    }

    /**
     * @return DbAdapter
     */
    private function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * Data cannot contain keys like idColumnName, levelColumnName, ...
     *
     * @param array $data
     * @return array
     */
    private function cleanData(array $data)
    {
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
     * @param Db\Sql\Select $dbSelect
     * @return void
     */
    public function setDefaultDbSelect(Db\Sql\Select $dbSelect)
    {
        $this->defaultDbSelect = $dbSelect;
    }

    /**
     * Return clone of default db select
     * @return Db\Sql\Select
     */
    public function getDefaultDbSelect()
    {
        $options = $this->getOptions();

        if (null == $this->defaultDbSelect) {
            $this->defaultDbSelect = new Db\Sql\Select($options->getTableName());
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    /**
    * @return LockSqlBuilderInterface
    */
    private function getLockSqlBuilder()
    {
        if (null == $this->lockSqlBuilder) {
            $vendorName = $this->getDbAdapter()
                               ->getDriver()
                               ->getDatabasePlatformName();

            $factory = new LockSqlBuilderFactory();
            $this->lockSqlBuilder = $factory->createAdapter($vendorName);
        }

        return $this->lockSqlBuilder;
    }

    public function lockTable()
    {
        $tableName = $this->getOptions()
                          ->getTableName();

        $sql = $this->getLockSqlBuilder()
                    ->getLockSqlString($tableName);

        if (null != $sql) {
            $this->getDbAdapter()
                 ->query($sql, DbAdapter::QUERY_MODE_EXECUTE);
        }
    }

    public function unlockTable()
    {
        $sql = $this->getLockSqlBuilder()
                    ->getUnlockSqlString();

        if (null != $sql) {
            $this->getDbAdapter()
                 ->query($sql, DbAdapter::QUERY_MODE_EXECUTE);
        }
    }

    public function beginTransaction()
    {
        $this->getDbAdapter()
             ->begin();
    }

    public function commitTransaction()
    {
        $this->getDbAdapter()
             ->commit();
    }

    public function rollbackTransaction()
    {
        $this->getDbAdapter()
             ->rollback();
    }

    public function update($nodeId, array $data, NodeInfo $nodeInfo = null)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        if (null == $nodeInfo) {
            $data = $this->cleanData($data);
        } else {
            $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
            $data[$options->getLevelColumnName()]    = $nodeInfo->getLevel();
            $data[$options->getLeftColumnName()]     = $nodeInfo->getLeft();
            $data[$options->getRightColumnName()]    = $nodeInfo->getRight();
        }

        $update = new Db\Sql\Update($options->getTableName());
        $update->set($data)
               ->where(array(
                    $options->getIdColumnName() => $nodeId,
               ));

        $dbAdapter->query($update->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function insert(NodeInfo $nodeInfo, array $data)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()]    = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()]     = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()]    = $nodeInfo->getRight();

        $insert = new Db\Sql\Insert($options->getTableName());
        $insert->values($data);
        $dbAdapter->query($insert->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $lastGeneratedValue = $dbAdapter->getDriver()
                                        ->getLastGeneratedValue($options->getSequenceName());

        return $lastGeneratedValue;
    }

    public function delete($leftIndex, $rightIndex)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $delete = new Db\Sql\Delete($options->getTableName());
        $delete->where
               ->greaterThanOrEqualTo($options->getLeftColumnName(), $leftIndex)
               ->AND
               ->lessThanOrEqualTo($options->getRightColumnName(), $rightIndex);

        $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function deleteAll($expectNodeId)
    {
        $options = $this->getOptions();
        $dbAdapter = $this->getDbAdapter();

        $delete = new Db\Sql\Delete;
        $delete->from($options->getTableName())
               ->where
               ->notEqualTo($options->getIdColumnName(), $expectNodeId);
        $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function moveLeftIndexes($fromIndex, $shift)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' = '
                        . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' + :shift'
                . ' WHERE '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' > :fromIndex';

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function moveRightIndexes($fromIndex, $shift)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' = '
                        . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
                . ' WHERE '
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' > :fromIndex';

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function updateParentId($nodeId, $newParentId)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $update = new Db\Sql\Update($options->getTableName());
        $update->set(array(
                    $options->getParentIdColumnName() => $newParentId,
               ))
               ->where(array(
                   $options->getIdColumnName() => $nodeId,
               ));

        $dbAdapter->query($update->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
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
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift)
    {
        if (0 == $shift) {
            return;
        }

        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();
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
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function getNode($nodeId)
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect()
                       ->where(array($options->getIdColumnName() =>  $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);

        $array = $result->toArray();

        if (0 < count($array)) {
            return $array[0];
        }
    }

    public function getNodeInfo($nodeId)
    {
        $options = $this->getOptions();
        $result = $this->getNode($nodeId);

        if (null == $result) {
            $result = null;
        } else {
            $id        = $result[$options->getIdColumnName()];
            $parentId  = $result[$options->getParentIdColumnName()];
            $level     = $result[$options->getLevelColumnName()];
            $left      = $result[$options->getLeftColumnName()];
            $right     = $result[$options->getRightColumnName()];

            $result = new NodeInfo($id, $parentId, $level, $left, $right);
        }

        return $result;
    }

    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false)
    {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;

        // node does not exist
        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();
        $select->where
               ->lessThanOrEqualTo($options->getLeftColumnName(), $nodeInfo->getLeft())
               ->AND
               ->greaterThanOrEqualTo($options->getRightColumnName(), $nodeInfo->getRight());

        $select->order($options->getLeftColumnName() . ' ASC');

        if (0 < $startLevel) {
            $select->where
                   ->greaterThanOrEqualTo($options->getLevelColumnName(), $startLevel);
        }

        if (true == $excludeLastNode) {
            $select->where
                   ->lessThan($options->getLevelColumnName(), $nodeInfo->getLevel());
        }

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        return $result->toArray();
    }

    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        $options = $this->getOptions();

        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($options->getLeftColumnName() . ' ASC');


        if (0 != $startLevel) {
            $level = $nodeInfo->getLevel() + (int) $startLevel;
            $select->where
                   ->greaterThanOrEqualTo($options->getLevelColumnName(), $level);
        }

        if (null != $levels) {
            $endLevel = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
            $select->where
                   ->lessThan($options->getLevelColumnName(), $endLevel);
        }

        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
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

        if (0 < count($resultArray)) {
            return $resultArray;
        }
    }
}
