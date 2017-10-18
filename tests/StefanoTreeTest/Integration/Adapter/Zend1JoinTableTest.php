<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\Zend1 as NestedSetAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;

class Zend1JoinTableTest extends AdapterJoinTableTestAbstract
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

        $adapter = new NestedSetAdapter($options, TestUtil::getZend1DbAdapter());

        $selectBuilder = function () {
            $select = TestUtil::getZend1DbAdapter()->select();
            $select->from(array('tree_traversal_with_scope'))
                ->joinLeft(
                    array('ttm' => 'tree_traversal_metadata'),
                    'ttm.tree_traversal_id = tree_traversal_with_scope.tree_traversal_id',
                    array('metadata' => 'name')
                );

            return $select;
        };

        $adapter->setDbSelectBuilder($selectBuilder);

        return $adapter;
    }
}
