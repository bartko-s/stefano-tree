<?php
namespace StefanoTree\NestedSet\Adapter;

use StefanoDb\Adapter\Adapter as DbAdapter;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use Zend\Db;

class Zend2DbAdapter
    implements AdapterInterface
{
    private $options;

    private $dbAdapter;

    private $defaultDbSelect = null;

    public function __construct(Options $options, DbAdapter $dbAdapter)
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
     * @return DbAdapter
     */
    private function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * Data cannot contain keys like idColumnName, levelColumnName, ...
     *
     * @param array $data
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
     * @param Db\Sql\Select $dbSelect
     * @return void
     */
    public function setDefaultDbSelect(Db\Sql\Select $dbSelect)
    {
        $this->defaultDbSelect = $dbSelect;
    }

    /**
     * Return clone of default db select
     * @return Db\Sql\Select
     */
    public function getDefaultDbSelect()
    {
        $options = $this->getOptions();

        if (null == $this->defaultDbSelect) {
            $this->defaultDbSelect = new Db\Sql\Select($options->getTableName());
        }

        $dbSelect = clone $this->defaultDbSelect;

        return $dbSelect;
    }

    public function lockTree($scope)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();
        $select->columns(array(
            'i' => $options->getIdColumnName(),
        ));

        if ($options->getScopeColumnName()) {
            $select->where(array(
                $options->getScopeColumnName() => $scope,
            ));
        }

        $sql = $select->getSqlString($dbAdapter->getPlatform()) . ' FOR UPDATE';

        $dbAdapter->query($sql, DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function beginTransaction()
    {
        $this->getDbAdapter()
             ->begin();
    }

    public function commitTransaction()
    {
        $this->getDbAdapter()
             ->commit();
    }

    public function rollbackTransaction()
    {
        $this->getDbAdapter()
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
        $data[$options->getLevelColumnName()]    = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()]     = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()]    = $nodeInfo->getRight();

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

    public function delete($leftIndex, $rightIndex, $scope=null)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $delete = new Db\Sql\Delete($options->getTableName());
        $delete->where
               ->greaterThanOrEqualTo($options->getLeftColumnName(), $leftIndex)
               ->AND
               ->lessThanOrEqualTo($options->getRightColumnName(), $rightIndex);

        if ($options->getScopeColumnName()) {
            $delete->where
                   ->AND
                   ->equalTo($options->getScopeColumnName(), $scope);
        }

        $dbAdapter->query($delete->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function moveLeftIndexes($fromIndex, $shift, $scope=null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' = '
                        . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' + :shift'
                . ' WHERE '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $dbPlatform->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbPlatform->quoteValue($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function moveRightIndexes($fromIndex, $shift, $scope=null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
                . ' SET '
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' = '
                        . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
                . ' WHERE '
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' > :fromIndex';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $dbPlatform->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbPlatform->quoteValue($scope);
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

    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift, $scope=null)
    {
        $options = $this->getOptions();

        if (0 == $shift) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
            . ' SET '
                . $dbPlatform->quoteIdentifier($options->getLevelColumnName()) . ' = '
                    . $dbPlatform->quoteIdentifier($options->getLevelColumnName()) . ' + :shift'
            . ' WHERE '
                . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' >= :leftFrom'
                . ' AND ' . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $dbPlatform->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbPlatform->quoteValue($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift, $scope=null)
    {
        if (0 == $shift) {
            return;
        }

        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();
        $dbPlatform = $dbAdapter->getPlatform();

        $sql = 'UPDATE ' . $dbPlatform->quoteIdentifier($options->getTableName())
            . ' SET '
                . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' = '
                    . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' + :shift, '
                . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' = '
                    . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' + :shift'
            . ' WHERE '
                . $dbPlatform->quoteIdentifier($options->getLeftColumnName()) . ' >= :leftFrom'
                . ' AND ' . $dbPlatform->quoteIdentifier($options->getRightColumnName()) . ' <= :rightTo';

        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $dbPlatform->quoteIdentifier($options->getScopeColumnName()) . ' = ' . $dbPlatform->quoteValue($scope);
        }

        $binds = array(
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        );

        $dbAdapter->query($sql)
                  ->execute($binds);
    }

    public function getRoots($scope=null)
    {
        $options = $this->getOptions();

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect();
        $select->where
            ->equalTo($options->getParentIdColumnName(),  0);

        if (null != $scope && $options->getScopeColumnName()) {
            $select->where
                ->equalTo($options->getScopeColumnName(), $scope);
        }

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        return $result->toArray();
    }

    public function getRoot($scope=null)
    {
        $roots = $this->getRoots($scope);

        return (0 < count($roots)) ?  $roots[0] : array();
    }

    public function getNode($nodeId)
    {
        $options = $this->getOptions();

        $nodeId = (int) $nodeId;

        $dbAdapter = $this->getDbAdapter();

        $select = $this->getDefaultDbSelect()
                       ->where(array($options->getIdColumnName() =>  $nodeId));

        $result = $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
                DbAdapter::QUERY_MODE_EXECUTE);

        $array = $result->toArray();

        if (0 < count($array)) {
            return $array[0];
        }
    }

    public function getNodeInfo($nodeId)
    {
        $options = $this->getOptions();
        $result = $this->getNode($nodeId);

        if (null == $result) {
            $result = null;
        } else {
            $id        = $result[$options->getIdColumnName()];
            $parentId  = $result[$options->getParentIdColumnName()];
            $level     = $result[$options->getLevelColumnName()];
            $left      = $result[$options->getLeftColumnName()];
            $right     = $result[$options->getRightColumnName()];

            if (isset($result[$options->getScopeColumnName()])) {
                $scope = $result[$options->getScopeColumnName()];
            } else {
                $scope = null;
            }

            $result = new NodeInfo($id, $parentId, $level, $left, $right, $scope);
        }

        return $result;
    }

    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false)
    {
        $options = $this->getOptions();

        $startLevel = (int) $startLevel;

        // node does not exist
        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return;
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

        $select->order($options->getLeftColumnName() . ' ASC');

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

    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        $options = $this->getOptions();

        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return;
        }

        $dbAdapter = $this->getDbAdapter();
        $select = $this->getDefaultDbSelect();
        $select->order($options->getLeftColumnName() . ' ASC');

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

        $result =  $dbAdapter->query($select->getSqlString($dbAdapter->getPlatform()),
            DbAdapter::QUERY_MODE_EXECUTE);

        $resultArray = $result->toArray();

        if (0 < count($resultArray)) {
            return $resultArray;
        }
    }
}
