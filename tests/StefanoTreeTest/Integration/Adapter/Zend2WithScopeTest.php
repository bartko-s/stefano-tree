<?php
namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\Zend2 as NestedSetAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;

class Zend2WithScopeTest
    extends AdapterWithScopeTestAbstract
{
    /**
     * @return TreeAdapterInterface
     */
    protected function getAdapter()
    {
        $options = new Options(array(
            'tableName' => 'tree_traversal_with_scope',
            'idColumnName' => 'tree_traversal_id',
            'scopeColumnName' => 'scope',
        ));

        if ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_with_scope_tree_traversal_id_seq');
        }

        return new NestedSetAdapter($options, TestUtil::getZend2DbAdapter());
    }
}
