<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use Doctrine\DBAL\Connection as DbConnection;
use Doctrine\DBAL\Query\QueryBuilder;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;

class Doctrine2DBAL extends AdapterAbstract implements AdapterInterface
{
    private $connection;

    /**
     * @param Options      $options
     * @param DbConnection $connection
     */
    public function __construct(Options $options, DbConnection $connection)
    {
        $this->setOptions($options);
        $this->setConnection($connection);
    }

    /**
     * @param DbConnection $dbAdapter
     */
    protected function setConnection(DbConnection $dbAdapter): void
    {
        $this->connection = $dbAdapter;
    }

    /**
     * @return DbConnection
     */
    private function getConnection(): DbConnection
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     *
     * @return QueryBuilder
     */
    public function getBlankDbSelect(): QueryBuilder
    {
        $queryBuilder = $this->getConnection()
                             ->createQueryBuilder();

        $queryBuilder->select(sprintf('%s.*', $this->getOptions()->getTableName()))
                     ->from($this->getOptions()->getTableName());

        return $queryBuilder;
    }

    /**
     * Return default db select. Always new instance.
     *
     * @return QueryBuilder
     */
    public function getDefaultDbSelect(): QueryBuilder
    {
        return $this->getDbSelectBuilder()();
    }

    /**
     * {@inheritdoc}
     */
    public function lockTree(): void
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $this->getBlankDbSelect();
        $sql->select($options->getIdColumnName(true).' AS i');

        $sql = $sql->getSQL().' FOR UPDATE';

        $connection->executeQuery($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->getConnection()
             ->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction(): void
    {
        $this->getConnection()
             ->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction(): void
    {
        $this->getConnection()
             ->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function update($nodeId, array $data): void
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

    /**
     * {@inheritdoc}
     */
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

        if (array_key_exists($options->getIdColumnName(), $data)) {
            return $data[$options->getIdColumnName()];
        } else {
            return $connection->lastInsertId($options->getSequenceName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($nodeId): void
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

    /**
     * {@inheritdoc}
     */
    public function moveLeftIndexes($fromIndex, $shift, $scope = null): void
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

    /**
     * {@inheritdoc}
     */
    public function moveRightIndexes($fromIndex, $shift, $scope = null): void
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

    /**
     * {@inheritdoc}
     */
    public function updateParentId($nodeId, $newParentId): void
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

    /**
     * {@inheritdoc}
     */
    public function updateLevels(int $leftIndexFrom, int $rightIndexTo, int $shift, $scope = null): void
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

    /**
     * {@inheritdoc}
     */
    public function moveBranch(int $leftIndexFrom, int $rightIndexTo, int $shift, $scope = null): void
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

    /**
     * {@inheritdoc}
     */
    public function getRoots($scope = null): array
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $this->getBlankDbSelect();
        $sql->where($options->getParentIdColumnName(true).' IS NULL');
        $sql->orderBy($options->getIdColumnName());

        $params = array();

        if (null != $scope && $options->getScopeColumnName()) {
            $sql->where($options->getScopeColumnName(true).' = :scope');
            $params[':scope'] = $scope;
        }

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $node = $stmt->fetchAll();

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot($scope = null): array
    {
        $roots = $this->getRoots($scope);

        return ($roots) ? $roots[0] : array();
    }

    /**
     * {@inheritdoc}
     */
    public function getNode($nodeId): ?array
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $connection = $this->getConnection();

        $sql = $this->getDefaultDbSelect();
        $sql->where($options->getIdColumnName(true).' = :id');

        $params = array(
            'id' => $nodeId,
        );

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $node = $stmt->fetch();

        return is_array($node) ? $node : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeInfo($nodeId): ?NodeInfo
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $connection = $this->getConnection();

        $sql = $this->getBlankDbSelect();
        $sql->where($options->getIdColumnName(true).' = :id');

        $params = array(
            'id' => $nodeId,
        );

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $node = $stmt->fetch();

        $data = is_array($node) ? $node : null;

        $result = ($data) ? $this->_buildNodeInfoObject($data) : null;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenNodeInfo($parentNodeId): array
    {
        $connection = $this->getConnection();
        $options = $this->getOptions();

        $sql = $this->getBlankDbSelect();

        $sql = $sql->where($options->getParentIdColumnName(true).' = :parentId')
                   ->orderBy($options->getLeftColumnName(true), 'ASC');

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

    /**
     * {@inheritdoc}
     */
    public function updateNodeMetadata(NodeInfo $nodeInfo): void
    {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getRightColumnName(), (string) $nodeInfo->getRight())
            ->set($options->getLeftColumnName(), (string) $nodeInfo->getLeft())
            ->set($options->getLevelColumnName(), (string) $nodeInfo->getLevel())
            ->where($options->getIdColumnName().' = :nodeId');

        $params = array(
            ':nodeId' => $nodeInfo->getId(),
        );

        $connection->executeUpdate($sql->getSQL(), $params);
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

        $connection = $this->getConnection();

        $sql = $this->getDefaultDbSelect();
        $params = array();

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName(true).' = :scope');
            $params['scope'] = $nodeInfo->getScope();
        }

        $sql->andWhere($options->getLeftColumnName(true).' <= :leftIndex')
            ->andWhere($options->getRightColumnName(true).' >= :rightIndex')
            ->orderBy($options->getLeftColumnName(true), 'ASC');

        $params['leftIndex'] = $nodeInfo->getLeft();
        $params['rightIndex'] = $nodeInfo->getRight();

        if (0 < $startLevel) {
            $sql->andWhere($options->getLevelColumnName(true).' >= :startLevel');

            $params['startLevel'] = $startLevel;
        }

        if (0 < $excludeLastNLevels) {
            $sql->andWhere($options->getLevelColumnName(true).' <= :level');

            $params['level'] = $nodeInfo->getLevel() - $excludeLastNLevels;
        }

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $result = $stmt->fetchAll();

        return (is_array($result)) ? $result : array();
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

        $connection = $this->getConnection();
        $sql = $this->getDefaultDbSelect();
        $sql->orderBy($options->getLeftColumnName(true), 'ASC');

        $params = array();

        if ($options->getScopeColumnName()) {
            $sql->andWhere($options->getScopeColumnName(true).' = :scope');
            $params['scope'] = $nodeInfo->getScope();
        }

        if (0 != $startLevel) {
            $sql->andWhere($options->getLevelColumnName(true).' >= :startLevel');

            $params['startLevel'] = $nodeInfo->getLevel() + $startLevel;
        }

        if (null != $levels) {
            $sql->andWhere($options->getLevelColumnName(true).'< :endLevel');
            $params['endLevel'] = $nodeInfo->getLevel() + $startLevel + abs($levels);
        }

        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $sql->andWhere('('.$options->getLeftColumnName(true).' BETWEEN :left AND :exLeftMinusOne'
                           .') OR ('.$options->getLeftColumnName(true).' BETWEEN :exRightPlusOne AND :right)')
                ->andWhere('('.$options->getRightColumnName(true).' BETWEEN :exRightPlusOne AND :right'
                           .') OR ('.$options->getRightColumnName(true).' BETWEEN :left AND :exLeftMinusOne)');

            $params['left'] = $nodeInfo->getLeft();
            $params['exLeftMinusOne'] = $excludeNodeInfo->getLeft() - 1;
            $params['exRightPlusOne'] = $excludeNodeInfo->getRight() + 1;
            $params['right'] = $nodeInfo->getRight();
        } else {
            $sql->andWhere($options->getLeftColumnName(true).' >= :left')
                ->andWhere($options->getRightColumnName(true).' <= :right');

            $params['left'] = $nodeInfo->getLeft();
            $params['right'] = $nodeInfo->getRight();
        }

        $stmt = $connection->executeQuery($sql->getSQL(), $params);

        $result = $stmt->fetchAll();

        return (0 < count($result)) ? $result : array();
    }
}
