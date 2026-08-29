<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration;

use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet as TreeAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\DbTester\ArrayDataSource;
use StefanoTreeTest\IntegrationTestCase;
use StefanoTreeTest\TestUtil;

/**
 * @internal
 */
class NestedSetWithScopeTest extends IntegrationTestCase
{
    protected ?TreeAdapter $treeAdapter = null;

    protected function tearDown(): void
    {
        $this->treeAdapter = null;
        parent::tearDown();
    }

    protected function getTreeAdapter(): TreeAdapter
    {
        if (null === $this->treeAdapter) {
            $options = new Options(array(
                'tableName' => 'tree_traversal_with_scope',
                'idColumnName' => 'tree_traversal_id',
                'scopeColumnName' => 'scope',
            ));

            $this->treeAdapter = new TreeAdapter($options, TestUtil::buildAdapter($options));
        }

        return $this->treeAdapter;
    }

    protected function getDataSet(): ArrayDataSource
    {
        switch ($this->name()) {
            case 'testInvalidTree':
            case 'testRebuildTree':
                return $this->createArrayDataSet(include __DIR__.'/_files/NestedSet/with_scope/initDataSetBrokenTreeIndexes.php');

            default:
                return $this->createArrayDataSet(include __DIR__.'/_files/NestedSet/with_scope/initDataSet.php');
        }
    }

    public function testCreateRoot(): void
    {
        $this->getTreeAdapter()
            ->createRootNode(array(), 10);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testCreateRoot.php');
    }

    public function testCreateRootRootWithSomeScopeAlreadyExist(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Root node for given scope already exist');

        $this->getTreeAdapter()
            ->createRootNode(array(), 123);
        $this->getTreeAdapter()
            ->createRootNode(array(), 123);
    }

    public function testGetRoots(): void
    {
        $expected = array(
            array(
                'tree_traversal_id' => 1,
                'name' => null,
                'lft' => 1,
                'rgt' => 10,
                'parent_id' => 0,
                'level' => 0,
                'scope' => 2,
            ),
            array(
                'tree_traversal_id' => 6,
                'name' => null,
                'lft' => 1,
                'rgt' => 6,
                'parent_id' => 0,
                'level' => 0,
                'scope' => 1,
            ),
        );

        $roots = $this->getTreeAdapter()
            ->getRoots();

        $this->assertEquals($expected, $roots);
    }

    public function testAddNodePlacementChildTopDefaultPlacement(): void
    {
        $lastGeneratedValue = $this->getTreeAdapter()
            ->addNode(1);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testAddNodePlacementChildTop.php');
        $this->assertEquals(9, $lastGeneratedValue);
    }

    public function testMoveNodePlacementBottom(): void
    {
        $this->getTreeAdapter()
            ->moveNode(3, 5, TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testMoveNodePlacementBottom.php');
    }

    public function testCannotMoveNodeBetweenScopes(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot move node between scopes.');

        $this->getTreeAdapter()
            ->moveNode(4, 8, TreeAdapter::PLACEMENT_CHILD_BOTTOM);
    }

    public function testDeleteBranch(): void
    {
        $this->getTreeAdapter()
            ->deleteBranch(2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testDeleteBranch.php');
    }

    public function testGetDescendants(): void
    {
        $expectedNodeData = array(
            array(
                'tree_traversal_id' => '2',
                'name' => null,
                'lft' => '2',
                'rgt' => '9',
                'parent_id' => '1',
                'level' => '1',
                'scope' => '2',
            ),
            array(
                'tree_traversal_id' => '3',
                'name' => null,
                'lft' => '3',
                'rgt' => '4',
                'parent_id' => '2',
                'level' => '2',
                'scope' => '2',
            ),
            array(
                'tree_traversal_id' => '4',
                'name' => null,
                'lft' => '5',
                'rgt' => '6',
                'parent_id' => '2',
                'level' => '2',
                'scope' => '2',
            ),
            array(
                'tree_traversal_id' => '5',
                'name' => null,
                'lft' => '7',
                'rgt' => '8',
                'parent_id' => '2',
                'level' => '2',
                'scope' => '2',
            ),
        );

        $nodeData = $this->getTreeAdapter()
            ->getDescendantsQueryBuilder()
            ->get(2);

        $this->assertEquals($expectedNodeData, $nodeData);
    }

    public function testGetAncestors(): void
    {
        $expectedNodeData = array(
            array(
                'tree_traversal_id' => '1',
                'name' => null,
                'lft' => '1',
                'rgt' => '10',
                'parent_id' => null,
                'level' => '0',
                'scope' => '2',
            ),
            array(
                'tree_traversal_id' => '2',
                'name' => null,
                'lft' => '2',
                'rgt' => '9',
                'parent_id' => '1',
                'level' => '1',
                'scope' => '2',
            ),
            array(
                'tree_traversal_id' => '5',
                'name' => null,
                'lft' => '7',
                'rgt' => '8',
                'parent_id' => '2',
                'level' => '2',
                'scope' => '2',
            ),
        );

        $nodeData = $this->getTreeAdapter()
            ->getAncestorsQueryBuilder()
            ->get(5);
        $this->assertEquals($expectedNodeData, $nodeData);
    }

    public function testUpdateCannotCorruptTreeStructure(): void
    {
        $excepted = array(
            'tree_traversal_id' => 4,
            'name' => 'updated',
            'lft' => 5,
            'rgt' => 6,
            'parent_id' => 2,
            'level' => 2,
            'scope' => 2,
        );

        $data = array(
            'tree_traversal_id' => 'corrupt data',
            'name' => 'updated',
            'lft' => 'corrupt data',
            'rgt' => 'corrupt data',
            'parent_id' => 'corrupt data',
            'level' => 'corrupt data',
            'scope' => 'corrupt data',
        );
        $this->getTreeAdapter()
            ->updateNode(4, $data);

        $this->assertEquals($excepted, $this->getTreeAdapter()->getNode(4));
    }

    public function testIsTreeValid(): void
    {
        $this->assertTrue($this->getTreeAdapter()->isValid(1));
    }

    public function testInvalidTree(): void
    {
        $this->assertFalse($this->getTreeAdapter()->isValid(1));
    }

    public function testValidateTreeGivenNodeIdIsNotRoot(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Given node is not root node.');

        $this->getTreeAdapter()->isValid(2);
    }

    public function testRebuildTree(): void
    {
        $this->getTreeAdapter()
            ->rebuild(1);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testRebuildTree.php');
    }

    public function testRebuildTreeGivenNodeIdIsNotRoot(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Given node is not root node.');

        $this->getTreeAdapter()->rebuild(5);
    }

    public function testIsValidTreeGivenNodeIdIsNotRoot(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Given node is not root node.');

        $this->getTreeAdapter()->isValid(4);
    }

    public function testRebuildTreeGivenNodeIdDoesNotExists(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Node does not exists.');

        $this->getTreeAdapter()->rebuild(999);
    }

    public function testIsValidTreeGivenNodeIdDoesNotExists(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Node does not exists.');

        $this->getTreeAdapter()->isValid(555);
    }
}
