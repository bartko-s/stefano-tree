<?php

namespace StefanoTree\NestedSet\Adapter;

use Doctrine\DBAL\Connection as DbConnection;
use Doctrine\DBAL\Query\QueryBuilder;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;

class Doctrine2DBAL implements AdapterInterface
{
    private $options;

    private $connection;

    private $defaultDbSelect;

    /**
     * @param Options      $options
     * @param DbConnection $connection
     */
    public function __construct(Options $options, DbConnection $connection)
    {
        $this->options = $options;
        $this->connection = $connection;
    }

    /**
     * @return Options
     */
    private function getOptions()
    {
        return $this->options;
    }

    /**
     * @return DbConnection
     */
    private function getConnection()
    {
        return $this->connection;
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
        );

        if (null !== $options->getScopeColumnName()) {
            $disallowedDataKeys[] = $options->getScopeColumnName();
        }

        return array_diff_key($data, array_flip($disallowedDataKeys));
    }

    /**
     * Return base db select without any join, etc.
     *
     * @return QueryBuilder
     */
    public function getBlankDbSelect()
    {
        $queryBuilder = $this->getConnection()
                             ->createQueryBuilder();

        $queryBuilder->select('*')
                     ->from($this->getOptions()->getTableName(), null);

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $dbSelect
     */
    public function setDefaultDbSelect(QueryBuilder $dbSelect)
    {
        $this->defaultDbSelect = $dbSelect;
    }

    /**
     * Return clone of default db select.
     *
     * @return QueryBuilder
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

        $connection = $this->getConnection();

        $sql = $this->getBlankDbSelect();
        $sql->select($options->getIdColumnName().' AS i');

        $sql = $sql->getSQL().' FOR UPDATE';

        $connection->executeQuery($sql);
    }

    public function beginTransaction()
    {
        $this->getConnection()
             ->beginTransaction();
    }

    public function commitTransaction()
    {
        $this->getConnection()
             ->commit();
    }

    public function rollbackTransaction()
    {
        $this->getConnection()
             ->rollBack();
    }

    public function update($nodeId, array $data)
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $data = $this->cleanData($data);

        $sql = $connection->createQueryBuilder();

        $sql->update($options->getTableName(), null)
            ->where($options->getIdColumnName().' = :'.$options->getIdColumnName());

        foreach ($data as $key => $value) {
            $sql->set($connection->quoteIdentifier($key), ':'.$key);
        }

        $data[$options->getIdColumnName()] = $nodeId;

        $connection->executeUpdate($sql->getSQL(), $data);
    }

    public function insert(NodeInfo $nodeInfo, array $data)
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()] = $nodeInfo->getRight();

        if ($options->getScopeColumnName()) {
            $data[$options->getScopeColumnName()] = $nodeInfo->getScope();
        }

        $connection->insert($options->getTableName(), $data);

        return $connection->lastInsertId($options->getSequenceName());
    }

    public function delete($nodeId)
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->delete($options->getTableName())
            ->where($options->getIdColumnName().' = :id');

        $params = array(
            ':id' => $nodeId,
        );

        $connection->executeQuery($sql->getSQL(), $params);
    }

    public function moveLeftIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getLeftColumnName(), $options->getLeftColumnName().' + :shift')
            ->where($options->getLeftColumnName().' > :fromIndex');

        $params = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName().' = :scope');
            $params[':scope'] = $scope;
        }

        $connection->executeUpdate($sql->getSQL(), $params);
    }

    public function moveRightIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getRightColumnName(), $options->getRightColumnName().' + :shift')
            ->where($options->getRightColumnName().' > :fromIndex');

        $params = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName().' = :scope');
            $params[':scope'] = $scope;
        }

        $connection->executeUpdate($sql->getSQL(), $params);
    }

    public function updateParentId($nodeId, $newParentId)
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getParentIdColumnName(), ':parentId')
            ->where($options->getIdColumnName().' = :nodeId');

        $params = array(
            ':parentId' => $newParentId,
            ':nodeId' => $nodeId,
        );

        $connection->executeUpdate($sql->getSQL(), $params);
    }

    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getLevelColumnName(), $options->getLevelColumnName().' + :shift')
            ->where($options->getLeftColumnName().' >= :leftFrom'
                    .' AND '.$options->getRightColumnName().' <= :rightTo');

        $params = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName().' = :scope');
            $params[':scope'] = $scope;
        }

        $connection->executeUpdate($sql->getSQL(), $params);
    }

    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
        if (0 == $shift) {
            return;
        }

        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getLeftColumnName(), $options->getLeftColumnName().' + :shift')
            ->set($options->getRightColumnName(), $options->getRightColumnName().' + :shift')
            ->where($options->getLeftColumnName().' >= :leftFrom'
                    .' AND '.$options->getRightColumnName().' <= :rightTo');

        $params = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName().' = :scope');
            $params[':scope'] = $scope;
        }

        $connection->executeUpdate($sql->getSQL(), $params);
    }

    public function getRoots($scope = null)
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $this->getBlankDbSelect();
        $sql->where($options->getParentIdColumnName().' IS NULL');
        $sql->orderBy($options->getIdColumnName());

        $params = array();

        if (null != $scope && $options->getScopeColumnName()) {
            $sql->where($options->getScopeColumnName().' = :scope');
            $params[':scope'] = $scope;
        }

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $node = $stmt->fetchAll();

        return $node;
    }

    public function getRoot($scope = null)
    {
        $roots = $this->getRoots($scope);

        return ($roots) ? $roots[0] : array();
    }

    public function getNode($nodeId)
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $connection = $this->getConnection();

        $sql = $this->getDefaultDbSelect();
        $sql->where($options->getIdColumnName().' = :'.$options->getIdColumnName());

        $params = array(
            $options->getIdColumnName() => $nodeId,
        );

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $node = $stmt->fetch();

        return is_array($node) ? $node : null;
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

        $connection = $this->getConnection();

        $sql = $this->getBlankDbSelect();
        $sql->where($options->getIdColumnName().' = :'.$options->getIdColumnName());

        $params = array(
            $options->getIdColumnName() => $nodeId,
        );

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $node = $stmt->fetch();

        $data = is_array($node) ? $node : null;

        $result = ($data) ? $this->_buildNodeInfoObject($data) : null;

        return $result;
    }

    public function getChildrenNodeInfo($parentNodeId)
    {
        $connection = $this->getConnection();
        $options = $this->getOptions();

        $queryBuilder = $connection->createQueryBuilder();

        $columns = array(
            $options->getIdColumnName(),
            $options->getLeftColumnName(),
            $options->getRightColumnName(),
            $options->getParentIdColumnName(),
            $options->getLevelColumnName(),
        );

        $sql = $queryBuilder->select($columns)
                            ->from($options->getTableName())
                            ->where($options->getParentIdColumnName().' = :parentId')
                            ->orderBy($options->getLeftColumnName(), 'ASC');

        $params = array(
            'parentId' => $parentNodeId,
        );

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $data = $stmt->fetchAll();

        $result = array();

        foreach ($data as $nodeData) {
            $result[] = $this->_buildNodeInfoObject($nodeData);
        }

        return $result;
    }

    public function updateNodeMetadata(NodeInfo $nodeInfo)
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getRightColumnName(), $nodeInfo->getRight())
            ->set($options->getLeftColumnName(), $nodeInfo->getLeft())
            ->set($options->getLevelColumnName(), $nodeInfo->getLevel())
            ->where($options->getIdColumnName().' = :nodeId');

        $params = array(
            ':nodeId' => $nodeInfo->getId(),
        );

        $connection->executeUpdate($sql->getSQL(), $params);
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

        $connection = $this->getConnection();

        $sql = $this->getDefaultDbSelect();
        $params = array();

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName().' = :scope');
            $params['scope'] = $nodeInfo->getScope();
        }

        $sql->andWhere($options->getLeftColumnName().' <= :leftIndex')
            ->andWhere($options->getRightColumnName().' >= :rightIndex')
            ->orderBy($options->getLeftColumnName(), 'ASC');

        $params['leftIndex'] = $nodeInfo->getLeft();
        $params['rightIndex'] = $nodeInfo->getRight();

        if (0 < $startLevel) {
            $sql->andWhere($options->getLevelColumnName().' >= :startLevel');

            $params['startLevel'] = $startLevel;
        }

        if (true == $excludeLastNode) {
            $sql->andWhere($options->getLevelColumnName().' < :level');

            $params['level'] = $nodeInfo->getLevel();
        }

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $result = $stmt->fetchAll();

        return (is_array($result)) ? $result : array();
    }

    public function getDescendants($nodeId, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        $options = $this->getOptions();

        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return array();
        }

        $connection = $this->getConnection();
        $sql = $this->getDefaultDbSelect();
        $sql->orderBy($options->getLeftColumnName(), 'ASC');

        $params = array();

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName().' = :scope');
            $params['scope'] = $nodeInfo->getScope();
        }

        if (0 != $startLevel) {
            $sql->andWhere($options->getLevelColumnName().' >= :startLevel');

            $params['startLevel'] = $nodeInfo->getLevel() + (int) $startLevel;
        }

        if (null != $levels) {
            $sql->andWhere($options->getLevelColumnName().'< :endLevel');
            $params['endLevel'] = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
        }

        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $sql->andWhere('('.$options->getLeftColumnName().' BETWEEN :left AND :exLeftMinusOne'
                           .') OR ('.$options->getLeftColumnName().' BETWEEN :exRightPlusOne AND :right)')
                ->andWhere('('.$options->getRightColumnName().' BETWEEN :exRightPlusOne AND :right'
                           .') OR ('.$options->getRightColumnName().' BETWEEN :left AND :exLeftMinusOne)');

            $params['left'] = $nodeInfo->getLeft();
            $params['exLeftMinusOne'] = $excludeNodeInfo->getLeft() - 1;
            $params['exRightPlusOne'] = $excludeNodeInfo->getRight() + 1;
            $params['right'] = $nodeInfo->getRight();
        } else {
            $sql->andWhere($options->getLeftColumnName().' >= :left')
                ->andWhere($options->getRightColumnName().' <= :right');

            $params['left'] = $nodeInfo->getLeft();
            $params['right'] = $nodeInfo->getRight();
        }

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $result = $stmt->fetchAll();

        return (0 < count($result)) ? $result : array();
    }
}
