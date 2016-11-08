<?php
namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use Zend_Db_Adapter_Abstract as ZendDbAdapter;

class Zend1
    implements AdapterInterface
{
    protected $options;

    protected $dbAdapter;

    protected $defaultDbSelect;

    public function __construct(Options $options, ZendDbAdapter $dbAdapter)
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
     * @return ZendDbAdapter
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    private function cleanData(array $data)
    {
        $options = $this->getOptions();

        $disallowedDataKeys = array(
            $options->getIdColumnName(),
            $options->getLeftColumnName(),
            $options->getRightColumnName(),
            $options->getLevelColumnName(),
            $options->getParentIdColumnName(),
            $options->getScopeColumnName(),
        );

        return array_diff_key($data, array_flip($disallowedDataKeys));
    }

    /**
     * Return base db select without any join, etc.
     * @return \Zend_Db_Select
     */
    public function getBlankDbSelect()
    {
        return $this->dbAdapter->select()->from($this->getOptions()->getTableName());
    }

    /**
     * @param \Zend_Db_Select $dbSelect
     * @return void
     */
    public function setDefaultDbSelect(\Zend_Db_Select $dbSelect)
    {
        $this->defaultDbSelect = $dbSelect;
    }

    /**
     * Return clone of default select
     *
     * @return \Zend_Db_Select
     */
    public function getDefaultDbSelect()
    {
        if (null == $this->defaultDbSelect) {
            $this->defaultDbSelect = $this->getBlankDbSelect();
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    public function lockTree($scope)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array('i' => $options->getIdColumnName()))
            ->forUpdate(true);

        if ($options->getScopeColumnName()) {
            $select->where($options->getScopeColumnName() . ' = ?', $scope);
        }

        $dbAdapter->fetchAll($select);
    }

    public function beginTransaction()
    {
        $this->getDbAdapter()->beginTransaction();
    }

    public function commitTransaction()
    {
        $this->getDbAdapter()->commit();
    }

    public function rollbackTransaction()
    {
        $this->getDbAdapter()->rollBack();
    }

    public function update($nodeId, array $data)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $data = $this->cleanData($data);

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()) . ' = ?' => $nodeId,
        );
        $dbAdapter->update($options->getTableName(), $data, $where);
    }

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

    public function delete($leftIndex, $rightIndex, $scope = null)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $where = array(
            $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' >= ?' => $leftIndex,
            $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' <= ?' => $rightIndex,
        );

        if ($options->getScopeColumnName()) {
            $where[$dbAdapter->quoteIdentifier($options->getScopeColumnName()) . ' = ?'] = $scope;
        }

        $dbAdapter->delete($options->getTableName(), $where);
    }

    public function moveLeftIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName())
            . ' SET ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName())
            . ' = ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' + :shift'
            . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '. $dbAdapter->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );
        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function moveRightIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();

        $sql = 'UPDATE ' . $dbAdapter->quoteIdentifier($options->getTableName())
            . ' SET ' . $dbAdapter->quoteIdentifier($options->getRightColumnName())
            . ' = ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
            . ' WHERE ' . $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '. $dbAdapter->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function updateParentId($nodeId, $newParentId)
    {
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

    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
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

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '. $dbAdapter->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
        if (0 == $shift) {
            return null;
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

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '. $dbAdapter->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbAdapter->quote($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->prepare($sql)->execute($binds);
    }

    public function getRoots($scope = null)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect()
            ->where($options->getParentIdColumnName() . ' = ?', 0);

        if (null != $scope && $options->getScopeColumnName()) {
            $select->where($options->getScopeColumnName() . ' = ?', $scope);
        }

        return $dbAdapter->fetchAll($select);
    }

    public function getRoot($scope = null)
    {
        $result = $this->getRoots($scope);

        return ($result) ? $result[0] : array();
    }

    public function getNode($nodeId)
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect()
            ->where($options->getIdColumnName() . ' = ?', $nodeId);

        $row = $dbAdapter->fetchRow($select);
        return $row ? $row : null;
    }

    /**
     * @param array $data
     * @return NodeInfo
     */
    private function _buildNodeInfoObject(array $data)
    {
        $options = $this->getOptions();

        $id        = $data[$options->getIdColumnName()];
        $parentId  = $data[$options->getParentIdColumnName()];
        $level     = $data[$options->getLevelColumnName()];
        $left      = $data[$options->getLeftColumnName()];
        $right     = $data[$options->getRightColumnName()];

        if (isset($data[$options->getScopeColumnName()])) {
            $scope = $data[$options->getScopeColumnName()];
        } else {
            $scope = null;
        }

        return new NodeInfo($id, $parentId, $level, $left, $right, $scope);
    }

    public function getNodeInfo($nodeId)
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect()
            ->where($options->getIdColumnName() . ' = ?', $nodeId);

        $row = $dbAdapter->fetchRow($select);

        $data = $row ? $row : null;

        $result = ($data) ? $this->_buildNodeInfoObject($data) : null;

        return $result;
    }

    public function getChildrenNodeInfo($parentNodeId)
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
        $select->order($options->getLeftColumnName());
        $select->where($options->getParentIdColumnName() . ' = ?', $parentNodeId);

        $data = $dbAdapter->fetchAll($select);

        $result = array();

        foreach ($data as $nodeData) {
            $result[] = $this->_buildNodeInfoObject($nodeData);
        }

        return $result;
    }

    public function updateNodeMetadata(NodeInfo $nodeInfo)
    {
        $dbAdapter = $this->getDbAdapter();
        $options = $this->getOptions();

        $bind = array(
            $options->getRightColumnName() => $nodeInfo->getRight(),
            $options->getLeftColumnName() => $nodeInfo->getLeft(),
            $options->getLevelColumnName() => $nodeInfo->getLevel(),
        );

        $where = array(
            $dbAdapter->quoteIdentifier($options->getIdColumnName()) . ' = ?' => $nodeInfo->getId(),
        );

        $dbAdapter->update($options->getTableName(), $bind, $where);
    }

    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false)
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
            $select->where($options->getScopeColumnName() .' = ?', $nodeInfo->getScope());
        }

        $select->where(
            $dbAdapter->quoteIdentifier($options->getLeftColumnName()) . ' <= ?', $nodeInfo->getLeft()
        )->where(
            $dbAdapter->quoteIdentifier($options->getRightColumnName()) . ' >= ?', $nodeInfo->getRight()
        )->order($options->getLeftColumnName() . ' ASC');

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

    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        $options = $this->getOptions();

        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return array();
        }

        $dbAdapter = $this->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($options->getLeftColumnName() . ' ASC');

        if ($options->getScopeColumnName()) {
            $select->where($options->getScopeColumnName() . ' = ?', $nodeInfo->getScope());
        }

        if (0 != $startLevel) {
            $level = $nodeInfo->getLevel() + (int) $startLevel;
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' >= ?', $level
            );
        }

        if (null != $levels) {
            $endLevel = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
            $select->where(
                $dbAdapter->quoteIdentifier($options->getLevelColumnName()) . ' < ?', $endLevel
            );
        }

        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $where = sprintf(
                "(%s OR %s) AND (%s OR %s)",
                $this->getWhereBetween($options->getLeftColumnName(), $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1),
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
