<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\IntegrationTestCase;
use StefanoTreeTest\TestUtil;

class JoinTableTest extends IntegrationTestCase
{
    /**
     * @return TreeAdapter
     */
    protected function getTreeAdapter()
    {
        $options = new Options(array(
                                   'tableName' => 'tree_traversal_with_scope',
                                   'idColumnName' => 'tree_traversal_id',
                                   'scopeColumnName' => 'scope',
                                   'dbSelectBuilder' => function () {
                                       return 'SELECT tree_traversal_with_scope.*, ttm.name AS metadata FROM tree_traversal_with_scope'
                                           .' LEFT JOIN tree_traversal_metadata AS ttm'
                                           .' ON ttm.tree_traversal_id = tree_traversal_with_scope.tree_traversal_id';
                                   },
                               ));

        if ('pgsql' == TEST_STEFANO_DB_VENDOR) {
            $options->setSequenceName('tree_traversal_with_scope_tree_traversal_id_seq');
        }

        return new TreeAdapter($options, TestUtil::buildAdapter($options));
    }

    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(__DIR__.'/_files/NestedSet/with_scope/initDataSet.xml');
    }

    public function testJoinTable()
    {
        $adapter = $this->getTreeAdapter();
        $result = $adapter->getDescendantsQueryBuilder()
                          ->levelLimit(2)
                          ->get(1);

        $expected = include __DIR__.'/_files/NestedSet/with_scope/testJoinTable.php';

        $this->assertEquals($expected, $result);
    }
}
