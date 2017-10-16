<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\Doctrine2DBAL as NestedSetAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;

class Doctrine2DBALJoinTableTest extends AdapterJoinTableTestAbstract
{
    /**
     * @return TreeAdapterInterface
     */
    protected function getAdapter()
    {
        $options = new Options(array(
            'tableName' => 'tree_traversal_with_scope',
            'tableAlias' => 'ttws',
            'idColumnName' => 'tree_traversal_id',
            'scopeColumnName' => 'scope',
        ));

        if ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_with_scope_tree_traversal_id_seq');
        }

        $adapter = new NestedSetAdapter($options, TestUtil::getDoctrine2Connection());

        $select = TestUtil::getDoctrine2Connection()->createQueryBuilder();
        $select->select('ttws.*', 'ttm.name AS metadata')
               ->from('tree_traversal_with_scope', 'ttws')
               ->leftJoin('ttws', 'tree_traversal_metadata', 'ttm', 'ttm.tree_traversal_id = ttws.tree_traversal_id');

        $adapter->setDefaultDbSelect($select);

        return $adapter;
    }
}
