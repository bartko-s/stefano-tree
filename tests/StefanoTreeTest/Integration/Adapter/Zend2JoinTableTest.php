<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\Zend2 as NestedSetAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;

class Zend2JoinTableTest extends AdapterJoinTableTestAbstract
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

        $adapter = new NestedSetAdapter($options, TestUtil::getZend2DbAdapter());

        $select = new \Zend\Db\Sql\Select('tree_traversal_with_scope');
        $select->join(
            array('ttm' => 'tree_traversal_metadata'),
            'ttm.tree_traversal_id = tree_traversal_with_scope.tree_traversal_id',
            array('metadata' => 'name'),
            $select::JOIN_LEFT
            );

        $adapter->setDefaultDbSelect($select);

        return $adapter;
    }
}
