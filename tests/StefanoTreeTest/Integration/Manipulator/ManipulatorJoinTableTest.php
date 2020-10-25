<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Manipulator;

use StefanoTree\NestedSet\Manipulator\Manipulator;
use StefanoTree\NestedSet\Manipulator\ManipulatorInterface;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\IntegrationTestCase;
use StefanoTreeTest\TestUtil;

/**
 * @internal
 * @coversNothing
 */
class ManipulatorJoinTableTest extends IntegrationTestCase
{
    /**
     * @var ManipulatorInterface
     */
    protected $manipulator;

    protected function setUp(): void
    {
        $this->manipulator = $this->getManipulator();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->manipulator = null;
        parent::tearDown();
    }

    /**
     * @return ManipulatorInterface
     */
    protected function getManipulator(): ManipulatorInterface
    {
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

        if ('pgsql' == TEST_STEFANO_DB_VENDOR) {
            $options->setSequenceName('tree_traversal_with_scope_tree_traversal_id_seq');
        }

        $manipulator = new Manipulator($options, TestUtil::buildAdapter($options));

        return $manipulator;
    }

    protected function getDataSet()
    {
        return $this->createArrayDataSet(include __DIR__.'/_files/adapter/join_table/initDataSet.php');
    }

    public function testGetNode()
    {
        $nodes = $this->manipulator
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

    public function testGetAncestors()
    {
        $nodes = $this->manipulator
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

    public function testGetDescendants()
    {
        $nodes = $this->manipulator
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

    public function testGetChildrenNodeInfo()
    {
        $nodes = $this->manipulator
            ->getChildrenNodeInfo(2);

        $this->assertEquals(3, count($nodes));
    }

    public function testGetNodeInfo()
    {
        $nodeInfo = $this->manipulator
            ->getNodeInfo(2);

        $this->assertEquals($nodeInfo->getId(), 2);
        $this->assertEquals($nodeInfo->getParentId(), 1);
        $this->assertEquals($nodeInfo->getLeft(), 2);
        $this->assertEquals($nodeInfo->getRight(), 9);
        $this->assertEquals($nodeInfo->getLevel(), 1);
        $this->assertEquals($nodeInfo->getScope(), 2);
    }
}
