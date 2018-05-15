<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use Zend\Db;
use Zend\Db\Adapter\Adapter as DbAdapter;

class Zend2 extends AdapterAbstract implements AdapterInterface
{
    private $dbAdapter;

    public function __construct(Options $options, DbAdapter $dbAdapter)
    {
        $this->setOptions($options);
        $this->setDbAdapter($dbAdapter);
    }

    /**
     * @param DbAdapter $dbAdapter
     */
    protected function setDbAdapter(DbAdapter $dbAdapter): void
    {
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @return DbAdapter
     */
    protected function getDbAdapter(): DbAdapter
    {
        return $this->dbAdapter;
    }

    /**
     * {@inheritdoc}
     *
     * @return Db\Sql\Select
     */
    public function getBlankDbSelect(): Db\Sql\Select
    {
        return new Db\Sql\Select($this->getOptions()->getTableName());
    }

    /**
     * Return default db select.
     *
     * @return Db\Sql\Select
     */
    public function getDefaultDbSelect()
    {
        return $this->getDbSelectBuilder()();
    }

    /**
     * {@inheritdoc}
     */
    public function lockTree(): void
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

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->getDbAdapter()
             ->getDriver()
             ->getConnection()
             ->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction(): void
    {
        $this->getDbAdapter()
             ->getDriver()
             ->getConnection()
             ->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction(): void
    {
        $this->getDbAdapter()
             ->getDriver()
             ->getConnection()
             ->rollback();
    }

    /**
     * {@inheritdoc}
     */
    public function update($nodeId, array $data): void
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

        $insert = new Db\Sql\Insert($options->getTableName());
        $insert->values($data);
        $dbAdapter->query($insert->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        if (array_key_exists($options->getIdColumnName(), $data)) {
            return $data[$options->getIdColumnName()];
        } else {
            $lastGeneratedValue = $dbAdapter->getDriver()
                                            ->getLastGeneratedValue($options->getSequenceName());

            return $lastGeneratedValue;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($nodeId): void
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $delete = new Db\Sql\Delete($options->getTableName());
        $delete->where
               ->equalTo($options->getIdColumnName(), $nodeId);

        $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
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

    /**
     * {@inheritdoc}
     */
    public function updateParentId($nodeId, $newParentId): void
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

    /**
     * {@inheritdoc}
     */
    public function getRoots($scope = null): array
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getBlankDbSelect();
        $select->where
            ->isNull($options->getParentIdColumnName(true));
        $select->order($options->getIdColumnName(true));

        if (null != $scope && $options->getScopeColumnName()) {
            $select->where
                ->equalTo($options->getScopeColumnName(true), $scope);
        }

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        return $result->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot($scope = null): array
    {
        $roots = $this->getRoots($scope);

        return (0 < count($roots)) ? $roots[0] : array();
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
                       ->where(array($options->getIdColumnName(true) => $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);

        $array = $result->toArray();

        return (0 < count($array)) ? $array[0] : null;
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
            ->where(array($options->getIdColumnName(true) => $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $array = $result->toArray();

        $result = ($array) ? $this->_buildNodeInfoObject($array[0]) : null;

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
        $select->columns($columns);
        $select->order($options->getLeftColumnName(true));
        $select->where(array(
            $options->getParentIdColumnName(true) => $parentNodeId,
        ));

        $data = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $result = array();

        foreach ($data->toArray() as $nodeData) {
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

    /**
     * {@inheritdoc}
     */
    public function getAncestors($nodeId, int $startLevel = 0, int $excludeLastNLevels = 0): array
    {
        $options = $this->getOptions();

        // node does not exist
        $nodeInfo = $this->getNodeInfo($nodeId);
        if (!$nodeInfo) {
            return array();
        }

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();

        if ($options->getScopeColumnName()) {
            $select->where
                ->equalTo($options->getScopeColumnName(true), $nodeInfo->getScope());
        }

        $select->where
               ->lessThanOrEqualTo($options->getLeftColumnName(true), $nodeInfo->getLeft())
               ->AND
               ->greaterThanOrEqualTo($options->getRightColumnName(true), $nodeInfo->getRight());

        $select->order($options->getLeftColumnName(true).' ASC');

        if (0 < $startLevel) {
            $select->where
                   ->greaterThanOrEqualTo($options->getLevelColumnName(true), $startLevel);
        }

        if (0 < $excludeLastNLevels) {
            $select->where
                   ->lessThanOrEqualTo($options->getLevelColumnName(true), $nodeInfo->getLevel() - $excludeLastNLevels);
        }

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        return $result->toArray();
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
            $select->where
                   ->equalTo($options->getScopeColumnName(true), $nodeInfo->getScope());
        }

        if (0 != $startLevel) {
            $level = $nodeInfo->getLevel() + $startLevel;
            $select->where
                   ->greaterThanOrEqualTo($options->getLevelColumnName(true), $level);
        }

        if (null != $levels) {
            $endLevel = $nodeInfo->getLevel() + $startLevel + abs($levels);
            $select->where
                   ->lessThan($options->getLevelColumnName(true), $endLevel);
        }

        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $select->where
                   ->NEST
                   ->between($options->getLeftColumnName(true),
                        $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
                   ->OR
                   ->between($options->getLeftColumnName(true),
                        $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight())
                   ->UNNEST
                   ->AND
                   ->NEST
                   ->between($options->getRightColumnName(true),
                        $excludeNodeInfo->getRight() + 1, $nodeInfo->getRight())
                   ->OR
                   ->between($options->getRightColumnName(true),
                        $nodeInfo->getLeft(), $excludeNodeInfo->getLeft() - 1)
                   ->UNNEST;
        } else {
            $select->where
                   ->greaterThanOrEqualTo($options->getLeftColumnName(true), $nodeInfo->getLeft())
                   ->AND
                   ->lessThanOrEqualTo($options->getRightColumnName(true), $nodeInfo->getRight());
        }

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $resultArray = $result->toArray();

        return (0 < count($resultArray)) ? $resultArray : array();
    }
}
