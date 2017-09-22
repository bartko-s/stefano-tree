<?php

namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoTree\NestedSet\Adapter\Zend2 as NestedSetAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;

class NestedSetWithZend2DbAdapterTest extends AbstractTest
{
    protected function getTreeAdapter()
    {
        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));

        if ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_tree_traversal_id_seq');
        }

        return new TreeAdapter(new NestedSetAdapter($options, TestUtil::getZend2DbAdapter()));
    }
}
