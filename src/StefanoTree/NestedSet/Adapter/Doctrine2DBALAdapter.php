<?php
namespace StefanoTree\NestedSet\Adapter;

use Doctrine\DBAL\Connection as DbConnection;
use Doctrine\DBAL\Query\QueryBuilder;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use StefanoLockTable\Factory as LockSqlBuilderFactory;
use StefanoLockTable\Adapter\AdapterInterface as LockSqlBuilderInterface;

class Doctrine2DBALAdapter
    implements AdapterInterface
{
    private $options;

    private $connection;

    private $defaultDbSelect;

    private $lockSqlBuilder;

    /**
     * @param Options $options
     * @param DbConnection $connection
     */
    public function __construct(Options $options, DbConnection $connection) {
        $this->options = $options;
        $this->connection = $connection;
    }

    /**
     * @return Options
     */
    private function getOptions() {
        return $this->options;
    }

    /**
     * @return DbAdapter
     */
    private function getConnection() {
        return $this->connection;
    }


    /**
     * Data cannot contain keys like idColumnName, levelColumnName, ...
     *
     * @param array $data
     * @return array
     */
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
     * @param QueryBuilder $dbSelect
     * @return this
     */
    public function setDefaultDbSelect(QueryBuilder $dbSelect) {
        $this->defaultDbSelect = $dbSelect;
        return $this;
    }

    /**
     * Return clone of default db select
     * @return QueryBuilder
     */
    public function getDefaultDbSelect() {
        $options = $this->getOptions();

        if(null == $this->defaultDbSelect) {
            $queryBuilder = $this->getConnection()
                                 ->createQueryBuilder();

            $queryBuilder->select('*')
                         ->from($options->getTableName(), null);
            
            $this->defaultDbSelect = $queryBuilder;
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    /**
     * @return LockSqlBuilderInterface
     */
    private function getLockSqlBuilder() {
        if(null == $this->lockSqlBuilder) {
            $vendorName = $this->getConnection()
                               ->getDatabasePlatform()
                               ->getName();

            $factory = new LockSqlBuilderFactory();
            $this->lockSqlBuilder = $factory->createAdapter($vendorName);
        }

        return $this->lockSqlBuilder;
    }


    public function lockTable() {
        $tableName = $this->getOptions()
                          ->getTableName();

        $sql = $this->getLockSqlBuilder()
                    ->getLockSqlString($tableName);

        if(null != $sql) {
            $this->getConnection()
                 ->executeQuery($sql);
        }
        
        return $this;
    }

    public function unlockTable() {
        $sql = $this->getLockSqlBuilder()
                    ->getUnlockSqlString();

        if(null != $sql) {
            $this->getConnection()
                 ->executeQuery($sql);
        }

          return $this;
    }

    public function beginTransaction() {
        $this->getConnection()
             ->beginTransaction();
        
        return $this;
    }

    public function commitTransaction() {
        $this->getConnection()
             ->commit();

        return $this;
    }

    public function rollbackTransaction() {
        $this->getConnection()
             ->rollBack();
        
        return $this;
    }

    public function update($nodeId, array $data, NodeInfo $nodeInfo = null) {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        if(null == $nodeInfo) {
            $data = $this->cleanData($data);
        } else {
            $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
            $data[$options->getLevelColumnName()]    = $nodeInfo->getLevel();
            $data[$options->getLeftColumnName()]     = $nodeInfo->getLeft();
            $data[$options->getRightColumnName()]    = $nodeInfo->getRight();
        }

        $sql = $connection->createQueryBuilder();

        $sql->update($options->getTableName(), null)
            ->where($options->getIdColumnName() . ' = :' . $options->getIdColumnName());

        foreach ($data as $key => $value) {
            $sql->set($connection->quoteIdentifier($key), ':' . $key);
        }

        $data[$options->getIdColumnName()] = $nodeId;

        $connection->executeUpdate($sql, $data);
        
        return $this;
    }

    public function insert(NodeInfo $nodeInfo, array $data) {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()]    = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()]     = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()]    = $nodeInfo->getRight();

        $connection->insert($options->getTableName(), $data);

        return $connection->lastInsertId();
    }

    public function delete($leftIndex, $rightIndex) {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->delete($options->getTableName())
            ->where($options->getLeftColumnName() . ' >= :leftIndex'
                . ' AND ' . $options->getRightColumnName() . ' <= :rightIndex');

        $params = array(
            ':leftIndex' => $leftIndex,
            ':rightIndex' => $rightIndex,
        );

        $connection->executeQuery($sql, $params);
        
        return $this;
    }

    public function deleteAll($expectNodeId) {
        $options = $this->getOptions();
        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->delete($options->getTableName())
            ->where($options->getIdColumnName() . ' != :nodeId');

        $params = array(
            ':nodeId' => $expectNodeId,
        );

        $connection->executeQuery($sql, $params);

        return $this;
    }

    public function moveLeftIndexes($fromIndex, $shift) {
        $options = $this->getOptions();

        if(0 == $shift) {
            return $this;
        }

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getLeftColumnName(), $options->getLeftColumnName() . ' + :shift')
            ->where($options->getLeftColumnName() . ' > :fromIndex');

        $params = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $connection->executeUpdate($sql, $params);

        return $this;
    }

    public function moveRightIndexes($fromIndex, $shift) {
        $options = $this->getOptions();

        if(0 == $shift) {
            return $this;
        }

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getRightColumnName(), $options->getRightColumnName() . ' + :shift')
            ->where($options->getRightColumnName() . ' > :fromIndex');

        $params = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $connection->executeUpdate($sql, $params);

        return $this;
    }

    public function updateParentId($nodeId, $newParentId) {
        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getParentIdColumnName(), ':parentId')
            ->where($options->getIdColumnName() . ' = :nodeId');

        $params = array(
            ':parentId' => $newParentId,
            ':nodeId' => $nodeId,
        );

        $connection->executeUpdate($sql, $params);
        
        return $this;
    }

    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift) {
        $options = $this->getOptions();

        if(0 == $shift) {
            return;
        }

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getLevelColumnName(), $options->getLevelColumnName() . ' + :shift')
            ->where($options->getLeftColumnName() . ' >= :leftFrom'
                    . ' AND ' . $options->getRightColumnName() . ' <= :rightTo');

        $params = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $connection->executeUpdate($sql, $params);

        return $this;
    }

    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift) {
        if(0 == $shift) {
            return;
        }

        $options = $this->getOptions();

        $connection = $this->getConnection();

        $sql = $connection->createQueryBuilder();
        $sql->update($options->getTableName())
            ->set($options->getLeftColumnName(), $options->getLeftColumnName() . ' + :shift')
            ->set($options->getRightColumnName(), $options->getRightColumnName() . ' + :shift')
            ->where($options->getLeftColumnName() . ' >= :leftFrom'
                . ' AND ' . $options->getRightColumnName() . ' <= :rightTo');

        $params = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $connection->executeUpdate($sql, $params);
    }

    public function getNode($nodeId) {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $connection = $this->getConnection();


        $sql = $this->getDefaultDbSelect();
        $sql->where($options->getIdColumnName() . ' = :' . $options->getIdColumnName());

        $params = array(
            $options->getIdColumnName() => $nodeId,
        );

        $stmt = $connection->executeQuery($sql, $params);

        $node = $stmt->fetch();

        if(is_array($node)) {
            return $node;
        }
    }

    public function getNodeInfo($nodeId) {
        $options = $this->getOptions();
        $result = $this->getNode($nodeId);

        if(null == $result) {
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

    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false) {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;

        // neexistuje
        if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }

        $connection = $this->getConnection();

        $sql = $this->getDefaultDbSelect();
        $sql->where($options->getLeftColumnName() . ' <= :leftIndex')
            ->andWhere($options->getRightColumnName() . ' >= :rightIndex')
            ->orderBy($options->getLeftColumnName(), 'ASC');

        $params = array(
            'leftIndex' => $nodeInfo->getLeft(),
            'rightIndex' => $nodeInfo->getRight(),
        );

        if(0 < $startLevel) {
            $sql->andWhere($options->getLevelColumnName() . ' >= :startLevel');

            $params['startLevel'] = $startLevel;
        }

        if(true == $excludeLastNode) {
            $sql->andWhere($options->getLevelColumnName() . ' < :level');

            $params['level'] = $nodeInfo->getLevel();
        }

        $stmt = $connection->executeQuery($sql, $params);

        $result = $stmt->fetchAll();

        if(is_array($result)) {
            return $result;
        }
    }

    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranche = null) {
        $options = $this->getOptions();

        if(!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return null;
        }

        $connection = $this->getConnection();
        $sql = $this->getDefaultDbSelect();
        $sql->orderBy($options->getLeftColumnName(), 'ASC');

        $params = array();

        if(0 != $startLevel) {
            $sql->andWhere($options->getLevelColumnName() . ' >= :startLevel');

            $params['startLevel'] = $nodeInfo->getLevel() + (int) $startLevel;
        }

        if(null != $levels) {
            $sql->andWhere($options->getLevelColumnName() . '< :endLevel');
            $params['endLevel'] = $nodeInfo->getLevel() + (int) $startLevel + abs($levels);
        }

        if(null != $excludeBranche && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranche))) {
            $sql->andWhere('(' . $options->getLeftColumnName() . ' BETWEEN :left AND :exLeftMinusOne'
                    . ') OR (' . $options->getLeftColumnName() . ' BETWEEN :exRightPlusOne AND :right)')
                ->andWhere('(' . $options->getRightColumnName() . ' BETWEEN :exRightPlusOne AND :right'
                    . ') OR (' . $options->getRightColumnName() . ' BETWEEN :left AND :exLeftMinusOne)');

            $params['left']           = $nodeInfo->getLeft();
            $params['exLeftMinusOne'] = $excludeNodeInfo->getLeft() - 1;
            $params['exRightPlusOne'] = $excludeNodeInfo->getRight() + 1;
            $params['right']          = $nodeInfo->getRight();
        } else {
            $sql->andWhere($options->getLeftColumnName() . ' >= :left')
                ->andWhere($options->getRightColumnName() . ' <= :right');

            $params['left']  = $nodeInfo->getLeft();
            $params['right'] = $nodeInfo->getRight();
        }

        $stmt = $connection->executeQuery($sql, $params);

        $result = $stmt->fetchAll();

        if(0 < count($result)) {
            return $result;
        }
    }
}