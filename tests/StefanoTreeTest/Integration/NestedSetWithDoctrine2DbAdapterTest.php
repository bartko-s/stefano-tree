<?php
namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoTree\NestedSet\Adapter\Doctrine2DBALAdapter;
use StefanoTree\NestedSet\Options;

class NestedSetWithDoctrine2DbAdapterTest
    extends AbstractTest
{
    protected function getTreeAdapter() {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname' => TEST_STEFANO_DB_DB_NAME,
            'user' => TEST_STEFANO_DB_USER,
            'password' => TEST_STEFANO_DB_PASSWORD,
            'host' => TEST_STEFANO_DB_HOSTNAME,
            'driver' => 'pdo_' . strtolower(TEST_STEFANO_DB_ADAPTER),
        );

        $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));

        if('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_tree_traversal_id_seq');
        }

        return new TreeAdapter(new Doctrine2DBALAdapter($options, $connection));
    }
}