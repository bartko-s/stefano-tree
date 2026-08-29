<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Manipulator;

use StefanoTree\NestedSet\Manipulator\Manipulator;
use StefanoTree\NestedSet\Manipulator\ManipulatorInterface;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\DbTester\ArrayDataSource;
use StefanoTreeTest\IntegrationTestCase;
use StefanoTreeTest\TestUtil;

/**
 * @internal
 */
class ManipulatorJoinTableTest extends IntegrationTestCase
{
    protected ?ManipulatorInterface $manipulator = null;

    protected function tearDown(): void
    {
        $this->manipulator = null;
        parent::tearDown();
    }

    protected function getManipulator(): ManipulatorInterface
    {
        if (null === $this->manipulator) {
            $options = new Options(array(
                'tableName' => 'tree_traversal_with_scope',
                'idColumnName' => 'tree_traversal_id',
                'scopeColumnName' => 'scope',
                'dbSelectBuilder' => function () {
                    $sql = 'SELECT tree_traversal_with_scope.*, ttm.name AS metadata FROM tree_traversal_with_scope'
                        .' LEFT JOIN tree_traversal_metadata AS ttm'
                        .' ON ttm.tree_traversal_id = tree_traversal_with_scope.tree_traversal_id';

                    return $sql;
                },
            ));

            $this->manipulator = new Manipulator($options, TestUtil::buildAdapter($options));
        }

        return $this->manipulator;
    }

    protected function getDataSet(): ArrayDataSource
    {
        return $this->createArrayDataSet(include __DIR__.'/_files/adapter/join_table/initDataSet.php');
    }

    public function testGetNode(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(10);

        $expected = array(
            array(
                'tree_traversal_id' => 10,
                'name' => null,
                'lft' => 5,
                'rgt' => 6,
                'parent_id' => 9,
                'level' => 4,
                'scope' => 1,
                'metadata' => 'meta-10',
            ),
        );
        $this->assertEquals($expected, $nodes);
    }

    public function testGetAncestors(): void
    {
        $nodes = $this->getManipulator()
            ->getAncestors(10, 2, 1);

        $expected = array(
            array(
                'tree_traversal_id' => 8,
                'name' => null,
                'lft' => 3,
                'rgt' => 8,
                'parent_id' => 7,
                'level' => 2,
                'scope' => 1,
                'metadata' => null,
            ),
            array(
                'tree_traversal_id' => 9,
                'name' => null,
                'lft' => 4,
                'rgt' => 7,
                'parent_id' => 8,
                'level' => 3,
                'scope' => 1,
                'metadata' => 'meta-9',
            ),
        );
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendants(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(2, 1, 1, 4);

        $expected = array(
            array(
                'tree_traversal_id' => 3,
                'name' => null,
                'lft' => 3,
                'rgt' => 4,
                'parent_id' => 2,
                'level' => 2,
                'scope' => 2,
                'metadata' => null,
            ),
            array(
                'tree_traversal_id' => 5,
                'name' => null,
                'lft' => 7,
                'rgt' => 8,
                'parent_id' => 2,
                'level' => 2,
                'scope' => 2,
                'metadata' => null,
            ),
        );
        $this->assertEquals($expected, $nodes);
    }

    public function testGetChildrenNodeInfo(): void
    {
        $nodes = $this->getManipulator()
            ->getChildrenNodeInfo(2);

        $this->assertEquals(3, count($nodes));
    }

    public function testGetNodeInfo(): void
    {
        $nodeInfo = $this->getManipulator()
            ->getNodeInfo(2);

        $this->assertNotNull($nodeInfo);
        $this->assertEquals($nodeInfo->getId(), 2);
        $this->assertEquals($nodeInfo->getParentId(), 1);
        $this->assertEquals($nodeInfo->getLeft(), 2);
        $this->assertEquals($nodeInfo->getRight(), 9);
        $this->assertEquals($nodeInfo->getLevel(), 1);
        $this->assertEquals($nodeInfo->getScope(), 2);
    }
}
