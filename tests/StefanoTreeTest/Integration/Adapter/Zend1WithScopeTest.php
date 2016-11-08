<?php
namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\Zend1 as NestedSetAdapter;
use StefanoTree\NestedSet\Options;


class Zend1WithScopeTest
    extends AdapterWithScopeTestAbstract
{
    /**
     * @return TreeAdapterInterface
     */
    protected function getAdapter()
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

        return new NestedSetAdapter($options, $dbAdapter);
    }
}
