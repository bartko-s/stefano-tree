<?php
namespace StefanoTreeTest\Integration\Adapter;

use Doctrine\DBAL;
use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\Doctrine2DBAL as NestedSetAdapter;
use StefanoTree\NestedSet\Options;

class Doctrine2DBALTest
    extends AdapterTestAbstract
{
    /**
     * @return TreeAdapterInterface
     */
    protected function getAdapter()
    {
        $config = new DBAL\Configuration();
        $connectionParams = array(
            'dbname' => TEST_STEFANO_DB_DB_NAME,
            'user' => TEST_STEFANO_DB_USER,
            'password' => TEST_STEFANO_DB_PASSWORD,
            'host' => TEST_STEFANO_DB_HOSTNAME,
            'driver' => 'pdo_' . strtolower(TEST_STEFANO_DB_ADAPTER),
        );

        $connection = DBAL\DriverManager::getConnection($connectionParams, $config);

        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));

        if ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_tree_traversal_id_seq');
        }

        return new NestedSetAdapter($options, $connection);
    }
}
