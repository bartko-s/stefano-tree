<?php
namespace StefanoTreeTest\Integration;

use StefanoTree\DbAdapter\Zend1DbWrapper;
use StefanoTree\NestedSet as TreeAdapter;
use StefanoTree\NestedSet\Adapter\Zend1DbAdapter;
use StefanoTree\NestedSet\Options;

class NestedSetWithZend1DbAdapterTest
    extends AbstractTest
{
    protected function getTreeAdapter() {
        $dbAdapter = \Zend_Db::factory('Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER), array(
            'hostname' => TEST_STEFANO_DB_HOSTNAME,
            'dbname' => TEST_STEFANO_DB_DB_NAME,
            'username' => TEST_STEFANO_DB_USER,
            'password' => TEST_STEFANO_DB_PASSWORD
        ));

        $adapter = new Zend1DbWrapper($dbAdapter);

        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));
        return new TreeAdapter(new Zend1DbAdapter($options, $adapter));
    }
}
