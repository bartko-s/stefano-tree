<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use Zend_Db_Adapter_Abstract as ZendDbAdapter;
use Zend_Db_Select as ZendDbSelect;

class Zend1 extends AdapterAbstract implements AdapterInterface
{
    protected $dbAdapter;

    protected $defaultDbSelect;

    public function __construct(Options $options, ZendDbAdapter $dbAdapter)
    {
        $this->setOptions($options);
        $this->setDbAdapter($dbAdapter);
    }

    /**
     * @param ZendDbAdapter $dbAdapter
     */
    protected function setDbAdapter(ZendDbAdapter $dbAdapter): void
    {
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @return ZendDbAdapter
     */
    public function getDbAdapter(): ZendDbAdapter
    {
        return $this->dbAdapter;
    }

    /**
     * Return base db select without any join, etc.
     *
     * @return ZendDbSelect
     */
    public function getBlankDbSelect(): ZendDbSelect
    {
        return $this->getDbAdapter()
            ->select()
            ->from($this->getOptions()->getTableName());
    }

    /**
     * @param ZendDbSelect $dbSelect
     */
    public function setDefaultDbSelect(ZendDbSelect $dbSelect): void
    {
        $this->defaultDbSelect = $dbSelect;
    }

    /**
     * Return clone of default select.
     *
     * @return ZendDbSelect
     */
    public function getDefaultDbSelect(): ZendDbSelect
    {
        if (null == $this->defaultDbSelect) {
            $this->defaultDbSelect = $this->getBlankDbSelect();
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    /**
     * {@inheritdoc}
     */
    public function lockTree(): void
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array('i' => $options->getIdColumnName(true)))
            ->forUpdate(true);

        $dbAdapter->fetchAll($select);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->getDbAdapter()->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction(): void
    {
        $this->getDbAdapter()->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction(): void
    {
        $this->getDbAdapter()->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function update($nodeId, array $data): void
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $data = $this->cleanData($data);

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()).' = ?' => $nodeId,
        );
        $dbAdapter->update($options->getTableName(), $data, $where);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(NodeInfo $nodeInfo, array $data)
    {
        $options = $this->getOptions();
        $dbAdapter = $this->getDbAdapter();

        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()] = $nodeInfo->getRight();

        if ($options->getScopeColumnName()) {
            $data[$options->getScopeColumnName()] = $nodeInfo->getScope();
        }

        $dbAdapter->insert($options->getTableName(), $data);
        if ('' != $options->getSequenceName()) {
            $lastGeneratedValue = $dbAdapter->lastSequenceId($options->getSequenceName());
        } else {
            $lastGeneratedValue = $dbAdapter->lastInsertId();
        }

        return $lastGeneratedValue;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($nodeId): void
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()).' = ?' => $nodeId,
        );

        $dbAdapter->delete($options->getTableName(), $where);
    }

    /**
     * {@inheritdoc}
     */
    public function moveLeftIndexes($fromIndex, $shift, $scope = null): void
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $sql = 'UPDATE '.$dbAdapter->quoteIdentifier($options->getTableName())
            .' SET '.$dbAdapter->quoteIdentifier($options->getLeftColumnName())
            .' = '.$dbAdapter->quoteIdentifier($options->getLeftColumnName()).' + :shift'
            .' WHERE '.$dbAdapter->quoteIdentifier($options->getLeftColumnName()).' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbAdapter->quoteIdentifier($options->getScopeColumnName()).' = '.$dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );
        $dbAdapter->prepare($sql)->execute($binds);
    }

    /**
     * {@inheritdoc}
     */
    public function moveRightIndexes($fromIndex, $shift, $scope = null): void
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE '.$dbAdapter->quoteIdentifier($options->getTableName())
            .' SET '.$dbAdapter->quoteIdentifier($options->getRightColumnName())
            .' = '.$dbAdapter->quoteIdentifier($options->getRightColumnName()).' + :shift'
            .' WHERE '.$dbAdapter->quoteIdentifier($options->getRightColumnName()).' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbAdapter->quoteIdentifier($options->getScopeColumnName()).' = '.$dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    /**
     * {@inheritdoc}
     */
    public function updateParentId($nodeId, $newParentId): void
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $bind = array(
            $options->getParentIdColumnName() => $newParentId,
        );

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()).' = ?' => $nodeId,
        );
        $dbAdapter->update($options->getTableName(), $bind, $where);
    }

    /**
     * {@inheritdoc}
     */
    public function updateLevels(int $leftIndexFrom, int $rightIndexTo, int $shift, $scope = null): void
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE '.$dbAdapter->quoteIdentifier($options->getTableName())
            .' SET '.$dbAdapter->quoteIdentifier($options->getLevelColumnName())
            .' = '.$dbAdapter->quoteIdentifier($options->getLevelColumnName()).' + :shift'
            .' WHERE '.$dbAdapter->quoteIdentifier($options->getLeftColumnName())
            .' >= :leftFrom'.' AND '.$dbAdapter->quoteIdentifier($options->getRightColumnName())
            .' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbAdapter->quoteIdentifier($options->getScopeColumnName()).' = '.$dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    /**
     * {@inheritdoc}
     */
    public function moveBranch(int $leftIndexFrom, int $rightIndexTo, int $shift, $scope = null): void
    {
        if (0 == $shift) {
            return;
        }

        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE '.$dbAdapter->quoteIdentifier($options->getTableName())
            .' SET '.$dbAdapter->quoteIdentifier($options->getLeftColumnName())
            .' = '.$dbAdapter->quoteIdentifier($options->getLeftColumnName()).' + :shift, '
            .$dbAdapter->quoteIdentifier($options->getRightColumnName())
            .' = '.$dbAdapter->quoteIdentifier($options->getRightColumnName()).' + :shift'
            .' WHERE '.$dbAdapter->quoteIdentifier($options->getLeftColumnName()).' >= :leftFrom'
            .' AND '.$dbAdapter->quoteIdentifier($options->getRightColumnName()).' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbAdapter->quoteIdentifier($options->getScopeColumnName()).' = '.$dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoots($scope = null): array
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect()
            ->where($options->getParentIdColumnName(true).' IS NULL')
            ->order($options->getIdColumnName(true));

        if (null != $scope && $options->getScopeColumnName()) {
            $select->where($options->getScopeColumnName(true).' = ?', $scope);
        }

        return $dbAdapter->fetchAll($select);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot($scope = null): array
    {
        $result = $this->getRoots($scope);

        return ($result) ? $result[0] : array();
    }

    /**
     * {@inheritdoc}
     */
    public function getNode($nodeId): ?array
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect()
            ->where($options->getIdColumnName(true).' = ?', $nodeId);

        $row = $dbAdapter->fetchRow($select);

        return $row ? $row : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeInfo($nodeId): ?NodeInfo
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect()
            ->where($options->getIdColumnName(true).' = ?', $nodeId);

        $row = $dbAdapter->fetchRow($select);

        $data = $row ? $row : null;

        $result = ($data) ? $this->_buildNodeInfoObject($data) : null;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenNodeInfo($parentNodeId): array
    {
        $dbAdapter = $this->getDbAdapter();
        $options = $this->getOptions();

        $columns = array(
            $options->getIdColumnName(),
            $options->getLeftColumnName(),
            $options->getRightColumnName(),
            $options->getParentIdColumnName(),
            $options->getLevelColumnName(),
        );

        if ($options->getScopeColumnName()) {
            $columns[] = $options->getScopeColumnName();
        }

        $select = $this->getBlankDbSelect();
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->columns($columns);
        $select->order($options->getLeftColumnName(true));
        $select->where($options->getParentIdColumnName(true).' = ?', $parentNodeId);

        $data = $dbAdapter->fetchAll($select);

        $result = array();

        foreach ($data as $nodeData) {
            $result[] = $this->_buildNodeInfoObject($nodeData);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function updateNodeMetadata(NodeInfo $nodeInfo): void
    {
        $dbAdapter = $this->getDbAdapter();
        $options = $this->getOptions();

        $bind = array(
            $options->getRightColumnName() => $nodeInfo->getRight(),
            $options->getLeftColumnName() => $nodeInfo->getLeft(),
            $options->getLevelColumnName() => $nodeInfo->getLevel(),
        );

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()).' = ?' => $nodeInfo->getId(),
        );

        $dbAdapter->update($options->getTableName(), $bind, $where);
    }

    /**
     * {@inheritdoc}
     */
    public function getAncestors($nodeId, int $startLevel = 0, int $excludeLastNLevels = 0): array
    {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;

        // node does not exist
        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return array();
        }

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();

        if ($options->getScopeColumnName()) {
            $select->where($options->getScopeColumnName(true).' = ?', $nodeInfo->getScope());
        }

        $select->where(
            $dbAdapter->quoteIdentifier($options->getLeftColumnName(true)).' <= ?', $nodeInfo->getLeft()
        )->where(
            $dbAdapter->quoteIdentifier($options->getRightColumnName(true)).' >= ?', $nodeInfo->getRight()
        )->order($options->getLeftColumnName(true).' ASC');

        if (0 < $startLevel) {
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName(true)).' >= ?', $startLevel
            );
        }

        if (0 < $excludeLastNLevels) {
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName(true)).' <= ?', $nodeInfo->getLevel() - $excludeLastNLevels
            );
        }

        $result = $dbAdapter->fetchAll($select);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescendants($nodeId, int $startLevel = 0, ?int $levels = null, $excludeBranch = null): array
    {
        $options = $this->getOptions();

        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return array();
        }

        $dbAdapter = $this->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($options->getLeftColumnName(true).' ASC');

        if ($options->getScopeColumnName()) {
            $select->where($options->getScopeColumnName(true).' = ?', $nodeInfo->getScope());
        }

        if (0 != $startLevel) {
            $level = $nodeInfo->getLevel() + (int) $startLevel;
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName(true)).' >= ?', $level
            );
        }

        if (null != $levels) {
            $endLevel = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName(true)).' < ?', $endLevel
            );
        }

        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $where = sprintf(
                '(%s OR %s) AND (%s OR %s)',
                $this->getWhereBetween($options->getLeftColumnName(true), $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1),
                $this->getWhereBetween($options->getLeftColumnName(true),
                    $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight()),
                $this->getWhereBetween($options->getRightColumnName(true),
                    $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight()),
                $this->getWhereBetween($options->getRightColumnName(true),
                    $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
            );
            $select->where($where);
        } else {
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLeftColumnName(true)).' >= ?', $nodeInfo->getLeft()
            );
            $select->where(
                $dbAdapter->quoteIdentifier($options->getRightColumnName(true)).' <= ?', $nodeInfo->getRight()
            );
        }

        $resultArray = $dbAdapter->fetchAll($select);

        return (0 < count($resultArray)) ? $resultArray : array();
    }

    protected function getWhereBetween($column, $first, $second)
    {
        $dbAdapter = $this->getDbAdapter();
        $quotedColumn = $dbAdapter->quoteIdentifier($column);
        $quotedFirst = $dbAdapter->quote($first);
        $quotedSecond = $dbAdapter->quote($second);

        return sprintf('(%s BETWEEN %s AND %s)', $quotedColumn, $quotedFirst, $quotedSecond);
    }
}
