<?php
namespace StefanoTreeTest\Integration\Adapter;

use StefanoDb\Adapter\Adapter as DbAdapter;
use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\StefanoDb as NestedSetAdapter;
use StefanoTree\NestedSet\Options;


class StefanoDbTest
    extends AdapterTestAbstract
{
    /**
     * @return TreeAdapterInterface
     */
    protected function getAdapter()
    {
        $dbAdapter = new DbAdapter(array(
            'driver' => 'Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER),
            'hostname' => TEST_STEFANO_DB_HOSTNAME,
            'database' => TEST_STEFANO_DB_DB_NAME,
            'username' => TEST_STEFANO_DB_USER,
            'password' => TEST_STEFANO_DB_PASSWORD
        ));

        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));

        if ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_tree_traversal_id_seq');
        }

        return new NestedSetAdapter($options, $dbAdapter);
    }

    public function testNestedTransactionCannotFail()
    {
        $adapter = $this->getAdapter();
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->commitTransaction();
        $adapter->commitTransaction();
    }

    public function testNestedTransactionCannotFail2()
    {
        $adapter = $this->getAdapter();
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->rollbackTransaction();
        $adapter->rollbackTransaction();
    }
}
