<?php
namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoDb\Adapter\Adapter as DbAdapter;
use StefanoTree\NestedSet\Adapter\Zend2DbAdapter;
use StefanoTree\NestedSet\Options;

class NestedSetWithZend2DbAdapterTest
    extends AbstractTest
{
    protected function getTreeAdapter() {
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
        return new TreeAdapter(new Zend2DbAdapter($options, $dbAdapter));
    }
}