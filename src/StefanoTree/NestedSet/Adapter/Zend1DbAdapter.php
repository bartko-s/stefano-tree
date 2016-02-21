<?php
namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\DbAdapter\Zend1DbWrapper;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use StefanoLockTable\Factory as LockSqlBuilderFactory;
use StefanoLockTable\Adapter\AdapterInterface as LockSqlBuilderInterface;

class Zend1DbAdapter implements AdapterInterface
{
    /**
     * @var Options
     */
    protected $options;

    /**
     * @var Zend1DbWrapper
     */
    protected $dbAdapter;

    /**
     * @var \Zend_Db_Select
     */
    protected $defaultDbSelect;

    /**
     * @var LockSqlBuilderFactory;
     */
    protected $lockSqlBuilder;

    public function __construct(Options $options, Zend1DbWrapper $dbAdapter) {
        $this->options = $options;
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * Lock tree table
     *
     * @return self
     */
    public function lockTable() {
        $options = $this->getOptions();
        $sql = $this->getLockSqlBuilder()->getLockSqlString($options->getTableName());

        if (null != $sql) {
            $this->getDbAdapter()->query($sql);
        }
        return $this;
    }

    /**
     * @return LockSqlBuilderInterface
     */
    private function getLockSqlBuilder() {
        if (null == $this->lockSqlBuilder) {
            $adapterClassname = $this->getDbAdapter()->getInternalAdapterClass();
            $parts = explode('_', $adapterClassname);
            $vendorName = end($parts);

            $factory = new LockSqlBuilderFactory();
            $this->lockSqlBuilder = $factory->createAdapter($vendorName);
        }

        return $this->lockSqlBuilder;
    }

    /**
     * Unlock tree table
     *
     * @return self
     */
    public function unlockTable() {
        $sql = $this->getLockSqlBuilder()->getUnlockSqlString();

        if (null != $sql) {
            $this->getDbAdapter()->query($sql);
        }
        return $this;
    }

    /**
     * Begin db transaction only if transaction has not been started before
     *
     * @return self
     */
    public function beginTransaction() {
        $this->getDbAdapter()->beginTransaction();
        return $this;
    }

    /**
     * Commit db transaction. Only if transaction start this class
     *
     * @return self
     */
    public function commitTransaction() {
        $this->getDbAdapter()->commit();
        return $this;
    }

    /**
     * Rollback db transaction
     *
     * @return self
     */
    public function rollbackTransaction() {
        $this->getDbAdapter()->rollBack();
        return $this;
    }

    /**
     * Update node data. Function must sanitize data from keys like level, leftIndex, ...
     *
     * @param int $nodeId
     * @param array $data
     * @param NodeInfo $nodeInfo
     * @return self
     */
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

        $quoteIdColumn = $dbAdapter->quoteIdentifier($options->getIdColumnName());
        $where = array(
            sprintf('%s = ?', $quoteIdColumn) => $nodeId,
        );

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        $where += $wheres;

        $dbAdapter->update($options->getTableName(), $data, $where);

        return $this;
    }

    /**
     * @param int $expectNodeId Delete all expect this node
     * @return self
     */
    public function deleteAll($expectNodeId) {
        $options = $this->getOptions();
        $db = $this->getDbAdapter();
        $quoteIdColumn = $db->quoteIdentifier($options->getIdColumnName());
        $where = array(
            sprintf('%s != ?', $quoteIdColumn) => $expectNodeId,
        );

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        $where += $wheres;

        $db->delete($options->getTableName(), $where);

        return $this;
    }

    /**
     * @param int $fromIndex Left index is greater than
     * @param int $shift
     * @return self
     */
    public function moveLeftIndexes($fromIndex, $shift) {
        $options = $this->getOptions();

        if (0 == $shift) {
            return $this;
        }

        $dbAdapter = $this->getDbAdapter();
        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName()) . ' SET ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' = ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' + :shift' . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' > :fromIndex';

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        if (count($wheres)) {
            $sql .= ' AND ' . implode(' ', $wheres);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );
        $dbAdapter->prepare($sql)->execute($binds);

        return $this;
    }

    /**
     * @param int $fromIndex Right index is greater than
     * @param int $shift
     * @return self
     */
    public function moveRightIndexes($fromIndex, $shift) {
        $options = $this->getOptions();

        if (0 == $shift) {
            return $this;
        }

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName()) . ' SET ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' = ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' + :shift' . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' > :fromIndex';

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        if (count($wheres)) {
            $sql .= ' AND ' . implode(' ', $wheres);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->prepare($sql)->execute($binds);

        return $this;
    }

    /**
     * @param int $nodeId
     * @param int $newParentId
     * @return self
     */
    public function updateParentId($nodeId, $newParentId) {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $bind = array(
            $options->getParentIdColumnName() => $newParentId,
        );

        $quotedIdCol = $dbAdapter->quoteIdentifier($options->getIdColumnName());
        $where = array(
            $quotedIdCol . ' = ?' => $nodeId,
        );

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        $where += $wheres;

        $dbAdapter->update($options->getTableName(), $bind, $where);

        return $this;
    }

    /**
     * @param int $nodeId
     * @return null|array
     */
    public function getNode($nodeId) {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect()->where(sprintf('%s = ?', $options->getIdColumnName()), $nodeId);

        $row = $dbAdapter->fetchRow($select);
        return $row ? $row : null;
    }

    /**
     * @param int $nodeId
     * @return NodeInfo|null
     */
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
            $this->defaultDbSelect = $this->dbAdapter->select()->from($options->getTableName());
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    /**
     * @param \Zend_Db_Select $dbSelect
     * @return $this
     */
    public function setDefaultDbSelect(\Zend_Db_Select $dbSelect) {
        $this->defaultDbSelect = $dbSelect;
        return $this;
    }

    /**
     * @return Options
     */
    private function getOptions() {
        return $this->options;
    }

    /**
     * @return Zend1DbWrapper
     */
    public function getDbAdapter() {
        return $this->dbAdapter;
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

    /**
     * @param NodeInfo $nodeInfo
     * @param array $data
     * @return int Last ID
     */
    public function insert(NodeInfo $nodeInfo, array $data) {
        $options = $this->getOptions();
        $dbAdapter = $this->getDbAdapter();

        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()] = $nodeInfo->getRight();

        $dbAdapter->insert($options->getTableName(), $data);
        $lastGeneratedValue = $dbAdapter->lastInsertId();

        return $lastGeneratedValue;
    }

    /**
     * Delete branch
     *
     * @param int $leftIndex Left index greater or equal to
     * @param int $rightIndex Right index greater or equal to
     * @return self
     */
    public function delete($leftIndex, $rightIndex) {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $where = array(
            sprintf('%s >= ?', $dbAdapter->quoteIdentifier($options->getLeftColumnName())) => $leftIndex,
            sprintf('%s <= ?', $dbAdapter->quoteIdentifier($options->getRightColumnName())) => $rightIndex,
        );

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        $where += $wheres;

        $dbAdapter->delete($options->getTableName(), $where);
        return $this;
    }

    /**
     * @param int $leftIndexFrom from left index or equal
     * @param int $rightIndexTo to right index or equal
     * @param int $shift shift
     */
    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift) {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName()) . ' SET ' . $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' = ' . $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' + :shift' . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' >= :leftFrom' . ' AND ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' <= :rightTo';

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        if (count($wheres)) {
            $sql .= ' AND ' . implode(' ', $wheres);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);

        return $this;
    }

    /**
     * @param int $leftIndexFrom from left index
     * @param int $rightIndexTo to right index
     * @param int $shift
     */
    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift) {
        if (0 == $shift) {
            return;
        }

        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName()) . ' SET ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' = ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' + :shift, ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' = ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' + :shift' . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' >= :leftFrom' . ' AND ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' <= :rightTo';

        $defaultSelect = $this->getDefaultDbSelect();
        $wheres = $defaultSelect->getPart(\Zend_Db_Select::WHERE);
        if (count($wheres)) {
            $sql .= ' AND ' . implode(' ', $wheres);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    /**
     * @param int $nodeId
     * @param int $startLevel 0 = include root
     * @param boolean $excludeLastNode
     * @return null|array
     */
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false) {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;

        // neexistuje
        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();
        $select->where(sprintf('%s <= ?', $dbAdapter->quoteIdentifier($options->getLeftColumnName())), $nodeInfo->getLeft());
        $select->where(sprintf('%s >= ?', $dbAdapter->quoteIdentifier($options->getRightColumnName())), $nodeInfo->getRight());
        $select->order($options->getLeftColumnName() . ' ASC');

        if (0 < $startLevel) {
            $select->where(sprintf('%s >= ?', $dbAdapter->quoteIdentifier($options->getLevelColumnName())), $startLevel);
        }

        if (true == $excludeLastNode) {
            $select->where(sprintf('%s < ?', $dbAdapter->quoteIdentifier($options->getLevelColumnName())), $nodeInfo->getLevel());
        }

        $result = $dbAdapter->fetchAll($select);

        return $result;
    }

    /**
     * @param int $nodeId
     * @param int $startLevel Relative level from $nodeId. 1 = exclude $nodeId from result.
     *                        2 = exclude 2 levels from result
     * @param int $levels Number of levels in the results relative to $startLevel
     * @param int $excludeBranch Exclude defined branch(node id) from result
     * @return null|array
     */
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
            $select->where($options->getLevelColumnName() . ' >= ?', $level);
        }

        if(null != $levels) {
            $endLevel = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
            $select->where($options->getLevelColumnName() . ' < ?', $endLevel);
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
            $select->where($options->getLeftColumnName() . ' >= ?', $nodeInfo->getLeft());
            $select->where($options->getRightColumnName() . ' <= ?', $nodeInfo->getRight());
        }

        $resultArray = $dbAdapter->fetchAll($select);

        if(0 < count($resultArray)) {
            return $resultArray;
        }
    }

    protected function getWhereBetween($column, $first, $second)
    {
        $db = $this->getDbAdapter();
        $quotedColumn = $db->quoteIdentifier($column);
        $quotedFirst = $db->quote($first);
        $quotedSecond = $db->quote($second);
        return sprintf('(%s BETWEEN %s AND %s)', $quotedColumn, $quotedFirst, $quotedSecond);
    }
}
