<?php
namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoTree\NestedSet\Adapter\Zend1DbAdapter;
use StefanoTree\NestedSet\Options;

class NestedSetWithZend1DbAdapterAndScopeTest
    extends AbstractScopeTest
{
    protected function getTreeAdapter()
    {
        $dbAdapter = \Zend_Db::factory('Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER), array(
            'host' => TEST_STEFANO_DB_HOSTNAME,
            'dbname' => TEST_STEFANO_DB_DB_NAME,
            'username' => TEST_STEFANO_DB_USER,
            'password' => TEST_STEFANO_DB_PASSWORD
        ));

        $options = new Options(array(
            'tableName' => 'tree_traversal_with_scope',
            'idColumnName' => 'tree_traversal_id',
            'scopeColumnName' => 'scope',
        ));

        if ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_with_scope_tree_traversal_id_seq');
        }

        return new TreeAdapter(new Zend1DbAdapter($options, $dbAdapter));
    }
}
