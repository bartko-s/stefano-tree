<?php
namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use StefanoLockTable\Factory as LockSqlBuilderFactory;
use StefanoLockTable\Adapter\AdapterInterface as LockSqlBuilderInterface;
use Zend_Db_Adapter_Abstract as ZendDbAdapter;

class Zend1DbAdapter
    extends NestedTransactionDbAdapterAbstract
    implements AdapterInterface
{
    protected $options;

    protected $dbAdapter;

    protected $defaultDbSelect;

    protected $lockSqlBuilder;

    public function __construct(Options $options, ZendDbAdapter $dbAdapter) {
        $this->options = $options;
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @return Options
     */
    private function getOptions() {
        return $this->options;
    }

    /**
     * @return ZendDbAdapter
     */
    public function getDbAdapter() {
        return $this->dbAdapter;
    }

    /**
     * @return LockSqlBuilderInterface
     */
    private function getLockSqlBuilder() {
        if (null == $this->lockSqlBuilder) {
            $adapterClassName = get_class($this->getDbAdapter());
            $parts = explode('_', $adapterClassName);
            $vendorName = end($parts);

            $factory = new LockSqlBuilderFactory();
            $this->lockSqlBuilder = $factory->createAdapter($vendorName);
        }

        return $this->lockSqlBuilder;
    }

    public function lockTable() {
        $options = $this->getOptions();
        $sql = $this->getLockSqlBuilder()->getLockSqlString($options->getTableName());

        if (null != $sql) {
            $this->getDbAdapter()->query($sql);
        }
    }

    public function unlockTable() {
        $sql = $this->getLockSqlBuilder()->getUnlockSqlString();

        if (null != $sql) {
            $this->getDbAdapter()->query($sql);
        }
    }

    protected function _isInTransaction() {
        return $this->getDbAdapter()
                    ->getConnection()
                    ->inTransaction();
    }

    protected function _beginTransaction() {
        $this->getDbAdapter()->beginTransaction();
    }

    protected function _commitTransaction() {
        $this->getDbAdapter()->commit();
    }

    protected function _rollbackTransaction() {
        $this->getDbAdapter()->rollBack();
    }

    public function update($nodeId, array $data, NodeInfo $nodeInfo = null) {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        if (null == $nodeInfo) {
            $data = $this->cleanData($data);
        } else {
            $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
            $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
            $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
            $data[$options->getRightColumnName()] = $nodeInfo->getRight();
        }

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()) . ' = ?' => $nodeId,
        );
        $dbAdapter->update($options->getTableName(), $data, $where);
    }

    public function deleteAll($expectNodeId) {
        $options = $this->getOptions();
        $dbAdapter = $this->getDbAdapter();

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()) . ' != ?' => $expectNodeId,
        );
        $dbAdapter->delete($options->getTableName(), $where);
    }

    public function moveLeftIndexes($fromIndex, $shift) {
        $options = $this->getOptions();

        if (0 == $shift) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();
        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName())
            . ' SET ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName())
                . ' = ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' + :shift'
            . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' > :fromIndex';

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );
        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function moveRightIndexes($fromIndex, $shift) {
        $options = $this->getOptions();

        if (0 == $shift) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName())
            . ' SET ' . $dbAdapter->quoteIdentifier($options->getRightColumnName())
                . ' = ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
            . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' > :fromIndex';

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function updateParentId($nodeId, $newParentId) {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $bind = array(
            $options->getParentIdColumnName() => $newParentId,
        );

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()) . ' = ?' => $nodeId,
        );
        $dbAdapter->update($options->getTableName(), $bind, $where);
    }

    public function getNode($nodeId) {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect()
                       ->where($options->getIdColumnName() . ' = ?', $nodeId);

        $row = $dbAdapter->fetchRow($select);
        return $row ? $row : null;
    }

    public function getNodeInfo($nodeId) {
        $options = $this->getOptions();
        $result = $this->getNode($nodeId);

        if (null == $result) {
            $result = null;
        } else {
            $id = $result[$options->getIdColumnName()];
            $parentId = $result[$options->getParentIdColumnName()];
            $level = $result[$options->getLevelColumnName()];
            $left = $result[$options->getLeftColumnName()];
            $right = $result[$options->getRightColumnName()];

            $result = new NodeInfo($id, $parentId, $level, $left, $right);
        }

        return $result;
    }

    /**
     * Return clone of default select
     *
     * @return \Zend_Db_Select
     */
    public function getDefaultDbSelect() {
        $options = $this->getOptions();

        if (null == $this->defaultDbSelect) {
            $this->defaultDbSelect = $this->dbAdapter->select()
                                          ->from($options->getTableName());
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    /**
     * @param \Zend_Db_Select $dbSelect
     * @return void
     */
    public function setDefaultDbSelect(\Zend_Db_Select $dbSelect) {
        $this->defaultDbSelect = $dbSelect;
    }

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

    public function insert(NodeInfo $nodeInfo, array $data) {
        $options = $this->getOptions();
        $dbAdapter = $this->getDbAdapter();

        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()] = $nodeInfo->getRight();

        $dbAdapter->insert($options->getTableName(), $data);
        if('' != $options->getSequenceName()) {
            $lastGeneratedValue = $dbAdapter->lastSequenceId($options->getSequenceName());
        } else {
            $lastGeneratedValue = $dbAdapter->lastInsertId();
        }

        return $lastGeneratedValue;
    }

    public function delete($leftIndex, $rightIndex) {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $where = array(
            $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' >= ?' => $leftIndex,
            $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' <= ?' => $rightIndex,
        );

        $dbAdapter->delete($options->getTableName(), $where);
    }

    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift) {
        $options = $this->getOptions();

        if (0 == $shift) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName())
            . ' SET ' . $dbAdapter->quoteIdentifier($options->getLevelColumnName())
                . ' = ' . $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' + :shift'
            . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName())
                . ' >= :leftFrom' . ' AND ' . $dbAdapter->quoteIdentifier($options->getRightColumnName())
                . ' <= :rightTo';

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift) {
        if (0 == $shift) {
            return;
        }

        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName())
            . ' SET ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName())
                . ' = ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' + :shift, '
            . $dbAdapter->quoteIdentifier($options->getRightColumnName())
                . ' = ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
            . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' >= :leftFrom'
                . ' AND ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' <= :rightTo';

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false) {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;

        // node does not exist
        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();
        $select->where(
            $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' <= ?', $nodeInfo->getLeft()
        );
        $select->where(
            $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' >= ?', $nodeInfo->getRight()
        );
        $select->order($options->getLeftColumnName() . ' ASC');

        if (0 < $startLevel) {
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' >= ?', $startLevel
            );
        }

        if (true == $excludeLastNode) {
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' < ?', $nodeInfo->getLevel()
            );
        }

        $result = $dbAdapter->fetchAll($select);

        return $result;
    }

    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null) {
        $options = $this->getOptions();

        if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($options->getLeftColumnName() . ' ASC');


        if(0 != $startLevel) {
            $level = $nodeInfo->getLevel() + (int) $startLevel;
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' >= ?', $level
            );
        }

        if(null != $levels) {
            $endLevel = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' < ?', $endLevel
            );
        }

        if(null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $where = sprintf(
                "(%s OR %s) AND (%s OR %s)",
                $this->getWhereBetween($options->getLeftColumnName(),$nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1),
                $this->getWhereBetween($options->getLeftColumnName(),
                    $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight()),
                $this->getWhereBetween($options->getRightColumnName(),
                    $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight()),
                $this->getWhereBetween($options->getRightColumnName(),
                    $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
            );
            $select->where($where);
        } else {
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' >= ?', $nodeInfo->getLeft()
            );
            $select->where(
                $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' <= ?', $nodeInfo->getRight()
            );
        }

        $resultArray = $dbAdapter->fetchAll($select);

        if(0 < count($resultArray)) {
            return $resultArray;
        }
    }

    protected function getWhereBetween($column, $first, $second) {
        $dbAdapter = $this->getDbAdapter();
        $quotedColumn = $dbAdapter->quoteIdentifier($column);
        $quotedFirst = $dbAdapter->quote($first);
        $quotedSecond = $dbAdapter->quote($second);
        return sprintf('(%s BETWEEN %s AND %s)', $quotedColumn, $quotedFirst, $quotedSecond);
    }
}
