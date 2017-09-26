<?php

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use Zend\Db;
use Zend\Db\Adapter\Adapter as DbAdapter;

class Zend2 implements AdapterInterface
{
    private $options;

    private $dbAdapter;

    private $defaultDbSelect = null;

    public function __construct(Options $options, $dbAdapter)
    {
        $this->setOptions($options);
        $this->setDbAdapter($dbAdapter);
    }

    /**
     * @param Options $options
     */
    private function setOptions(Options $options)
    {
        $this->options = $options;
    }

    /**
     * @return Options
     */
    private function getOptions()
    {
        return $this->options;
    }

    /**
     * @param DbAdapter $dbAdapter
     *
     * @throws InvalidArgumentException
     */
    protected function setDbAdapter($dbAdapter)
    {
        if (!$dbAdapter instanceof DbAdapter) {
            throw new InvalidArgumentException(
                'DbAdapter must be instance of "%s" but instance of "%s" was given', DbAdapter::class, get_class($dbAdapter)
            );
        }

        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @return DbAdapter
     */
    protected function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * Data cannot contain keys like idColumnName, levelColumnName, ...
     *
     * @param array $data
     *
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
            $options->getScopeColumnName(),
        );

        return array_diff_key($data, array_flip($disallowedDataKeys));
    }

    /**
     * Return base db select without any join, etc.
     *
     * @return Db\Sql\Select
     */
    public function getBlankDbSelect()
    {
        return new Db\Sql\Select($this->getOptions()->getTableName());
    }

    /**
     * @param Db\Sql\Select $dbSelect
     */
    public function setDefaultDbSelect(Db\Sql\Select $dbSelect)
    {
        $this->defaultDbSelect = $dbSelect;
    }

    /**
     * Return default db select.
     *
     * @return Db\Sql\Select
     */
    public function getDefaultDbSelect()
    {
        if (null === $this->defaultDbSelect) {
            $this->defaultDbSelect = $this->getBlankDbSelect();
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    public function lockTree()
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect();
        $select->columns(array(
            'i' => $options->getIdColumnName(),
        ));

        $sql = $select->getSqlString($dbAdapter->getPlatform()).' FOR UPDATE';

        $dbAdapter->query($sql, DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function beginTransaction()
    {
        $this->getDbAdapter()
             ->getDriver()
             ->getConnection()
             ->beginTransaction();
    }

    public function commitTransaction()
    {
        $this->getDbAdapter()
             ->getDriver()
             ->getConnection()
             ->commit();
    }

    public function rollbackTransaction()
    {
        $this->getDbAdapter()
             ->getDriver()
             ->getConnection()
             ->rollback();
    }

    public function update($nodeId, array $data)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $data = $this->cleanData($data);

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
        $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()] = $nodeInfo->getRight();

        if ($options->getScopeColumnName()) {
            $data[$options->getScopeColumnName()] = $nodeInfo->getScope();
        }

        $insert = new Db\Sql\Insert($options->getTableName());
        $insert->values($data);
        $dbAdapter->query($insert->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $lastGeneratedValue = $dbAdapter->getDriver()
                                        ->getLastGeneratedValue($options->getSequenceName());

        return $lastGeneratedValue;
    }

    public function delete($nodeId)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $delete = new Db\Sql\Delete($options->getTableName());
        $delete->where
               ->equalTo($options->getIdColumnName(), $nodeId);

        $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function moveLeftIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE '.$dbPlatform->quoteIdentifier($options->getTableName())
                .' SET '
                    .$dbPlatform->quoteIdentifier($options->getLeftColumnName()).' = '
                        .$dbPlatform->quoteIdentifier($options->getLeftColumnName()).' + :shift'
                .' WHERE '
                    .$dbPlatform->quoteIdentifier($options->getLeftColumnName()).' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbPlatform->quoteIdentifier($options->getScopeColumnName()).' = '.$dbPlatform->quoteValue($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function moveRightIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE '.$dbPlatform->quoteIdentifier($options->getTableName())
                .' SET '
                    .$dbPlatform->quoteIdentifier($options->getRightColumnName()).' = '
                        .$dbPlatform->quoteIdentifier($options->getRightColumnName()).' + :shift'
                .' WHERE '
                    .$dbPlatform->quoteIdentifier($options->getRightColumnName()).' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbPlatform->quoteIdentifier($options->getScopeColumnName()).' = '.$dbPlatform->quoteValue($scope);
        }

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

    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return null;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE '.$dbPlatform->quoteIdentifier($options->getTableName())
            .' SET '
                .$dbPlatform->quoteIdentifier($options->getLevelColumnName()).' = '
                    .$dbPlatform->quoteIdentifier($options->getLevelColumnName()).' + :shift'
            .' WHERE '
                .$dbPlatform->quoteIdentifier($options->getLeftColumnName()).' >= :leftFrom'
                .' AND '.$dbPlatform->quoteIdentifier($options->getRightColumnName()).' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbPlatform->quoteIdentifier($options->getScopeColumnName()).' = '.$dbPlatform->quoteValue($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
        if (0 == $shift) {
            return null;
        }

        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE '.$dbPlatform->quoteIdentifier($options->getTableName())
            .' SET '
                .$dbPlatform->quoteIdentifier($options->getLeftColumnName()).' = '
                    .$dbPlatform->quoteIdentifier($options->getLeftColumnName()).' + :shift, '
                .$dbPlatform->quoteIdentifier($options->getRightColumnName()).' = '
                    .$dbPlatform->quoteIdentifier($options->getRightColumnName()).' + :shift'
            .' WHERE '
                .$dbPlatform->quoteIdentifier($options->getLeftColumnName()).' >= :leftFrom'
                .' AND '.$dbPlatform->quoteIdentifier($options->getRightColumnName()).' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$dbPlatform->quoteIdentifier($options->getScopeColumnName()).' = '.$dbPlatform->quoteValue($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function getRoots($scope = null)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect();
        $select->where
            ->isNull($options->getParentIdColumnName());
        $select->order($options->getIdColumnName());

        if (null != $scope && $options->getScopeColumnName()) {
            $select->where
                ->equalTo($options->getScopeColumnName(), $scope);
        }

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        return $result->toArray();
    }

    public function getRoot($scope = null)
    {
        $roots = $this->getRoots($scope);

        return (0 < count($roots)) ? $roots[0] : array();
    }

    public function getNode($nodeId)
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect()
                       ->where(array($options->getIdColumnName() => $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);

        $array = $result->toArray();

        return (0 < count($array)) ? $array[0] : null;
    }

    /**
     * @param array $data
     *
     * @return NodeInfo
     */
    private function _buildNodeInfoObject(array $data)
    {
        $options = $this->getOptions();

        $id = $data[$options->getIdColumnName()];
        $parentId = $data[$options->getParentIdColumnName()];
        $level = $data[$options->getLevelColumnName()];
        $left = $data[$options->getLeftColumnName()];
        $right = $data[$options->getRightColumnName()];

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
            ->where(array($options->getIdColumnName() => $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $array = $result->toArray();

        $result = ($array) ? $this->_buildNodeInfoObject($array[0]) : null;

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
        $select->columns($columns);
        $select->order($options->getLeftColumnName());
        $select->where(array(
            $options->getParentIdColumnName() => $parentNodeId,
        ));

        $data = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $result = array();

        foreach ($data->toArray() as $nodeData) {
            $result[] = $this->_buildNodeInfoObject($nodeData);
        }

        return $result;
    }

    public function updateNodeMetadata(NodeInfo $nodeInfo)
    {
        $dbAdapter = $this->getDbAdapter();
        $options = $this->getOptions();

        $update = new Db\Sql\Update($options->getTableName());

        $update->set(array(
            $options->getRightColumnName() => $nodeInfo->getRight(),
            $options->getLeftColumnName() => $nodeInfo->getLeft(),
            $options->getLevelColumnName() => $nodeInfo->getLevel(),
        ));

        $update->where(array(
            $options->getIdColumnName() => $nodeInfo->getId(),
        ));

        $dbAdapter->query($update->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false)
    {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;

        // node does not exist
        $nodeInfo = $this->getNodeInfo($nodeId);
        if (!$nodeInfo) {
            return array();
        }

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();

        if ($options->getScopeColumnName()) {
            $select->where
                ->equalTo($options->getScopeColumnName(), $nodeInfo->getScope());
        }

        $select->where
               ->lessThanOrEqualTo($options->getLeftColumnName(), $nodeInfo->getLeft())
               ->AND
               ->greaterThanOrEqualTo($options->getRightColumnName(), $nodeInfo->getRight());

        $select->order($options->getLeftColumnName().' ASC');

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

    public function getDescendants($nodeId, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        $options = $this->getOptions();

        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return array();
        }

        $dbAdapter = $this->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($options->getLeftColumnName().' ASC');

        if ($options->getScopeColumnName()) {
            $select->where
                   ->equalTo($options->getScopeColumnName(), $nodeInfo->getScope());
        }

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

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $resultArray = $result->toArray();

        return (0 < count($resultArray)) ? $resultArray : array();
    }
}
