<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Manipulator;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;

class Manipulator implements ManipulatorInterface
{
    private $adapter;

    private $options;

    public function __construct(Options $options, AdapterInterface $adapter)
    {
        $this->setOptions($options);
        $this->setAdapter($adapter);
    }

    /**
     * @param \StefanoTree\NestedSet\Adapter\AdapterInterface $adapter
     */
    private function setAdapter(AdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param Options $options
     */
    protected function setOptions(Options $options): void
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Data cannot contain keys like idColumnName, levelColumnName, ...
     *
     * @param array $data
     *
     * @return array
     */
    protected function cleanData(array $data): array
    {
        $options = $this->getOptions();

        $disallowedDataKeys = array(
            $options->getIdColumnName(),
            $options->getLeftColumnName(),
            $options->getRightColumnName(),
            $options->getLevelColumnName(),
            $options->getParentIdColumnName(),
        );

        if (null !== $options->getScopeColumnName()) {
            $disallowedDataKeys[] = $options->getScopeColumnName();
        }

        return array_diff_key($data, array_flip($disallowedDataKeys));
    }

    /**
     * @param array $data
     *
     * @return NodeInfo
     */
    protected function _buildNodeInfoObject(array $data)
    {
        $options = $this->getOptions();

        $id = $data[$options->getIdColumnName()];
        $parentId = $data[$options->getParentIdColumnName()];
        $level = (int) $data[$options->getLevelColumnName()];
        $left = (int) $data[$options->getLeftColumnName()];
        $right = (int) $data[$options->getRightColumnName()];

        if (isset($data[$options->getScopeColumnName()])) {
            $scope = $data[$options->getScopeColumnName()];
        } else {
            $scope = null;
        }

        return new NodeInfo($id, $parentId, $level, $left, $right, $scope);
    }

    /**
     * @return callable
     */
    public function getDbSelectBuilder(): callable
    {
        return $this->getOptions()->getDbSelectBuilder() ?? function() {
            return $this->getBlankDbSelect();
        };
    }

    /**
     * @return string
     */
    public function getBlankDbSelect(): string
    {
        return 'SELECT * FROM '.$this->getAdapter()->quoteIdentifier($this->getOptions()->getTableName()).' ';
    }

    /**
     * Return default db select.
     *
     * @return string
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
        $adapter = $this->getAdapter();

        $sql = 'SELECT '.$adapter->quoteIdentifier($options->getIdColumnName())
            .' FROM '.$adapter->quoteIdentifier($options->getTableName())
            .' FOR UPDATE';

        $adapter->executeSQL($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->getAdapter()
             ->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction(): void
    {
        $this->getAdapter()
             ->commitTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction(): void
    {
        $this->getAdapter()
             ->rollbackTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function update($nodeId, array $data): void
    {
        $options = $this->getOptions();
        $adapter = $this->getAdapter();
        $data = $this->cleanData($data);

        $setPart = array_map(function ($item) use ($adapter) {
            return $adapter->quoteIdentifier($item).' = :'.$item;
        }, array_keys($data));

        $sql = 'UPDATE '.$adapter->quoteIdentifier($options->getTableName())
            .' SET '.implode(', ', $setPart)
            .' WHERE '.$adapter->quoteIdentifier($options->getIdColumnName()).' = :__nodeID';

        $data['__nodeID'] = $nodeId;

        $adapter->executeSQL($sql, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(NodeInfo $nodeInfo, array $data)
    {
        $options = $this->getOptions();

        $adapter = $this->getAdapter();

        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()] = $nodeInfo->getRight();

        if ($options->getScopeColumnName()) {
            $data[$options->getScopeColumnName()] = $nodeInfo->getScope();
        }

        $columns = array_map(function ($item) use ($adapter) {
            return $adapter->quoteIdentifier($item);
        }, array_keys($data));

        $values = array_map(function ($item) {
            return ':'.$item;
        }, array_keys($data));

        $sql = 'INSERT INTO '.$adapter->quoteIdentifier($options->getTableName())
            .' ('.implode(', ', $columns).')'
            .' VALUES('.implode(', ', $values).')';

        return $adapter->executeInsertSQL($sql, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($nodeId): void
    {
        $options = $this->getOptions();
        $adapter = $this->getAdapter();

        $sql = 'DELETE FROM '.$adapter->quoteIdentifier($options->getTableName())
            .' WHERE '.$adapter->quoteIdentifier($options->getIdColumnName()).' = :__nodeID';

        $params = array(
            '__nodeID' => $nodeId,
        );

        $adapter->executeSQL($sql, $params);
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

        $adapter = $this->getAdapter();

        $params = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $sql = 'UPDATE '.$adapter->quoteIdentifier($options->getTableName())
            .' SET '
            .$adapter->quoteIdentifier($options->getLeftColumnName()).' = '
            .$adapter->quoteIdentifier($options->getLeftColumnName()).' + :shift'
            .' WHERE '
            .$adapter->quoteIdentifier($options->getLeftColumnName()).' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getScopeColumnName()).' = :__scope';
            $params['__scope'] = $scope;
        }

        $adapter->executeSQL($sql, $params);
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

        $adapter = $this->getAdapter();

        $params = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $sql = 'UPDATE '.$adapter->quoteIdentifier($options->getTableName())
            .' SET '
            .$adapter->quoteIdentifier($options->getRightColumnName()).' = '
            .$adapter->quoteIdentifier($options->getRightColumnName()).' + :shift'
            .' WHERE '
            .$adapter->quoteIdentifier($options->getRightColumnName()).' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getScopeColumnName()).' = :__scope';
            $params['__scope'] = $scope;
        }

        $adapter->executeSQL($sql, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function updateParentId($nodeId, $newParentId): void
    {
        $options = $this->getOptions();

        $adapter = $this->getAdapter();

        $sql = 'UPDATE '.$adapter->quoteIdentifier($options->getTableName())
            .' SET '.$adapter->quoteIdentifier($options->getParentIdColumnName()).' = :__parentId'
            .' WHERE '.$adapter->quoteIdentifier($options->getIdColumnName()).' = :__nodeId';

        $params = array(
            '__parentId' => $newParentId,
            '__nodeId' => $nodeId,
        );

        $adapter->executeSQL($sql, $params);
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

        $adapter = $this->getAdapter();

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $sql = 'UPDATE '.$adapter->quoteIdentifier($options->getTableName())
            .' SET '
            .$adapter->quoteIdentifier($options->getLevelColumnName()).' = '
            .$adapter->quoteIdentifier($options->getLevelColumnName()).' + :shift'
            .' WHERE '
            .$adapter->quoteIdentifier($options->getLeftColumnName()).' >= :leftFrom'
            .' AND '.$adapter->quoteIdentifier($options->getRightColumnName()).' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getScopeColumnName()).' = :__scope';
            $binds['__scope'] = $scope;
        }

        $adapter->executeSQL($sql, $binds);
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

        $adapter = $this->getAdapter();

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $sql = 'UPDATE '.$adapter->quoteIdentifier($options->getTableName())
            .' SET '
            .$adapter->quoteIdentifier($options->getLeftColumnName()).' = '
            .$adapter->quoteIdentifier($options->getLeftColumnName()).' + :shift, '
            .$adapter->quoteIdentifier($options->getRightColumnName()).' = '
            .$adapter->quoteIdentifier($options->getRightColumnName()).' + :shift'
            .' WHERE '
            .$adapter->quoteIdentifier($options->getLeftColumnName()).' >= :leftFrom'
            .' AND '.$adapter->quoteIdentifier($options->getRightColumnName()).' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getScopeColumnName()).' = :__scope';
            $binds['__scope'] = $scope;
        }

        $adapter->executeSQL($sql, $binds);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoots($scope = null): array
    {
        $options = $this->getOptions();

        $adapter = $this->getAdapter();

        $params = array();

        $sql = $this->getBlankDbSelect();
        $sql .= ' WHERE '.$adapter->quoteIdentifier($options->getParentIdColumnName(true)).' IS NULL';

        if (null != $scope && $options->getScopeColumnName()) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getScopeColumnName(true)).' = :__scope';
            $params['__scope'] = $scope;
        }

        $sql .= ' ORDER BY '.$adapter->quoteIdentifier($options->getIdColumnName(true)).' ASC';

        return $adapter->executeSelectSQL($sql, $params);
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
        $adapter = $this->getAdapter();

        $params = array(
            '__nodeID' => $nodeId,
        );

        $sql = $this->getDefaultDbSelect();
        $sql .= ' WHERE '.$adapter->quoteIdentifier($options->getIdColumnName(true)).' = :__nodeID';

        $result = $adapter->executeSelectSQL($sql, $params);

        return (0 < count($result)) ? $result[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeInfo($nodeId): ?NodeInfo
    {
        $options = $this->getOptions();
        $adapter = $this->getAdapter();

        $params = array(
            '__nodeID' => $nodeId,
        );

        $sql = $this->getBlankDbSelect();
        $sql .= ' WHERE '.$adapter->quoteIdentifier($options->getIdColumnName(true)).' = :__nodeID';

        $array = $adapter->executeSelectSQL($sql, $params);

        $result = ($array) ? $this->_buildNodeInfoObject($array[0]) : null;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenNodeInfo($parentNodeId): array
    {
        $adapter = $this->getAdapter();
        $options = $this->getOptions();

        $params = array(
            '__parentID' => $parentNodeId,
        );

        $sql = 'SELECT *'
            .' FROM '.$adapter->quoteIdentifier($this->getOptions()->getTableName())
            .' WHERE '.$adapter->quoteIdentifier($options->getParentIdColumnName(true)).' = :__parentID'
            .' ORDER BY '.$adapter->quoteIdentifier($options->getLeftColumnName(true)).' ASC';

        $data = $adapter->executeSelectSQL($sql, $params);

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
        $adapter = $this->getAdapter();
        $options = $this->getOptions();

        $data = array(
            $options->getRightColumnName() => $nodeInfo->getRight(),
            $options->getLeftColumnName() => $nodeInfo->getLeft(),
            $options->getLevelColumnName() => $nodeInfo->getLevel(),
        );

        $setPart = array_map(function ($item) use ($adapter) {
            return $adapter->quoteIdentifier($item).' = :'.$item;
        }, array_keys($data));

        $sql = 'UPDATE '.$adapter->quoteIdentifier($options->getTableName())
            .' SET '.implode(', ', $setPart)
            .' WHERE '.$adapter->quoteIdentifier($options->getIdColumnName()).' = :__nodeID';

        $data['__nodeID'] = $nodeInfo->getId();

        $adapter->executeSQL($sql, $data);
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

        $adapter = $this->getAdapter();

        $params = array(
            '__leftIndex' => $nodeInfo->getLeft(),
            '__rightIndex' => $nodeInfo->getRight(),
        );

        $sql = $this->getDefaultDbSelect();

        $sql .= ' WHERE '.$adapter->quoteIdentifier($options->getLeftColumnName(true)).' <= :__leftIndex'
            .' AND '.$adapter->quoteIdentifier($options->getRightColumnName(true)).' >= :__rightIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getScopeColumnName(true)).' = :__scope';
            $params['__scope'] = $nodeInfo->getScope();
        }

        if (0 < $startLevel) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getLevelColumnName(true)).' >= :__startLevel';
            $params['__startLevel'] = $startLevel;
        }

        if (0 < $excludeLastNLevels) {
            $sql .= ' AND '.$adapter->quoteIdentifier($options->getLevelColumnName(true)).' <= :__excludeLastNLevels';
            $params['__excludeLastNLevels'] = $nodeInfo->getLevel() - $excludeLastNLevels;
        }

        $sql .= ' ORDER BY '.$adapter->quoteIdentifier($options->getLeftColumnName(true)).' ASC';

        return $adapter->executeSelectSQL($sql, $params);
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

        $adapter = $this->getAdapter();
        $sql = $this->getDefaultDbSelect();

        $params = array();
        $wherePart = array();

        if ($options->getScopeColumnName()) {
            $wherePart[] = $adapter->quoteIdentifier($options->getScopeColumnName(true)).' = :__scope';
            $params['__scope'] = $nodeInfo->getScope();
        }

        if (0 != $startLevel) {
            $wherePart[] = $adapter->quoteIdentifier($options->getLevelColumnName(true)).' >= :__level';
            $params['__level'] = $nodeInfo->getLevel() + $startLevel;
        }

        if (null != $levels) {
            $wherePart[] = $adapter->quoteIdentifier($options->getLevelColumnName(true)).' < :__endLevel';
            $params['__endLevel'] = $nodeInfo->getLevel() + $startLevel + abs($levels);
        }

        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $wherePart[] = '( '
                .$adapter->quoteIdentifier($options->getLeftColumnName(true)).' BETWEEN :__l1 AND :__p1'
                .' OR '
                .$adapter->quoteIdentifier($options->getLeftColumnName(true)).' BETWEEN :__l2 AND :__p2'
                .') AND ('
                .$adapter->quoteIdentifier($options->getRightColumnName(true)).' BETWEEN :__l3 AND :__p3'
                .' OR '
                .$adapter->quoteIdentifier($options->getRightColumnName(true)).' BETWEEN :__l4 AND :__p4'
                .')';

            $params['__l1'] = $nodeInfo->getLeft();
            $params['__p1'] = $excludeNodeInfo->getLeft() - 1;
            $params['__l2'] = $excludeNodeInfo->getRight() + 1;
            $params['__p2'] = $nodeInfo->getRight();
            $params['__l3'] = $excludeNodeInfo->getRight() + 1;
            $params['__p3'] = $nodeInfo->getRight();
            $params['__l4'] = $nodeInfo->getLeft();
            $params['__p4'] = $excludeNodeInfo->getLeft() - 1;
        } else {
            $wherePart[] = $adapter->quoteIdentifier($options->getLeftColumnName(true)).' >= :__left'
                .' AND '.$adapter->quoteIdentifier($options->getRightColumnName(true)).' <= :__right';

            $params['__left'] = $nodeInfo->getLeft();
            $params['__right'] = $nodeInfo->getRight();
        }

        $sql .= ' WHERE '.implode(' AND ', $wherePart);
        $sql .= ' ORDER BY '.$adapter->quoteIdentifier($options->getLeftColumnName(true)).' ASC';

        $result = $adapter->executeSelectSQL($sql, $params);

        return (0 < count($result)) ? $result : array();
    }
}
