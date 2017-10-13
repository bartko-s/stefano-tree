<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoTreeTest\IntegrationTestCase;

abstract class AbstractScopeTest extends IntegrationTestCase
{
    /**
     * @var TreeAdapter
     */
    protected $treeAdapter;

    protected function setUp()
    {
        $this->treeAdapter = $this->getTreeAdapter();

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->treeAdapter = null;
        parent::tearDown();
    }

    /**
     * @return TreeAdapter
     */
    abstract protected function getTreeAdapter();

    protected function getDataSet()
    {
        switch ($this->getName()) {
            case 'testValidateTreeRaiseExceptionIfIdParentIdIsBroken':
                return $this->createMySQLXMLDataSet(__DIR__.'/_files/NestedSet/with_scope/initDataSetBrokenParents.xml');
            case 'testInvalidTree':
            case 'testRebuildTree':
                return $this->createMySQLXMLDataSet(__DIR__.'/_files/NestedSet/with_scope/initDataSetBrokenTreeIndexes.xml');
            default:
                return $this->createMySQLXMLDataSet(__DIR__.'/_files/NestedSet/with_scope/initDataSet.xml');
        }
    }

    public function testCreateRoot()
    {
        $this->treeAdapter
             ->createRootNode(array(), 10);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testCreateRoot.xml');
    }

    public function testCreateRootRootWithSomeScopeAlreadyExist()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Root node for given scope already exist');

        $this->treeAdapter
            ->createRootNode(array(), 123);
        $this->treeAdapter
            ->createRootNode(array(), 123);
    }

    public function testGetRoots()
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

        $roots = $this->treeAdapter
                      ->getRoots();

        $this->assertEquals($expected, $roots);
    }

    public function testAddNodePlacementChildTopDefaultPlacement()
    {
        $lastGeneratedValue = $this->treeAdapter
            ->addNode(1);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testAddNodePlacementChildTop.xml');
        $this->assertEquals(9, $lastGeneratedValue);
    }

    public function testMoveNodePlacementBottom()
    {
        $this->treeAdapter
             ->moveNode(3, 5, TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testMoveNodePlacementBottom.xml');
    }

    public function testCannotMoveNodeBetweenScopes()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot move node between scopes.');

        $this->treeAdapter
             ->moveNode(4, 8, TreeAdapter::PLACEMENT_CHILD_BOTTOM);
    }

    public function testDeleteBranch()
    {
        $this->treeAdapter
            ->deleteBranch(2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testDeleteBranch.xml');
    }

    public function testGetDescendants()
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

        $nodeData = $this->treeAdapter
                       ->getDescendants(2);
        $this->assertEquals($expectedNodeData, $nodeData);
    }

    public function testGetPath()
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

        $nodeData = $this->treeAdapter
            ->getPath(5);
        $this->assertEquals($expectedNodeData, $nodeData);
    }

    public function testUpdateCannotCorruptTreeStructure()
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
        $this->treeAdapter
             ->updateNode(4, $data);

        $this->assertEquals($excepted, $this->treeAdapter->getNode(4));
    }

    public function testIsTreeValid()
    {
        $this->assertTrue($this->treeAdapter->isValid(1));
    }

    public function testInvalidTree()
    {
        $this->assertFalse($this->treeAdapter->isValid(1));
    }

    public function testValidateTreeGivenNodeIdIsNotRoot()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Given node is not root node.');

        $this->treeAdapter->isValid(2);
    }

    public function testRebuildTree()
    {
        $this->treeAdapter
             ->rebuild(1);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/NestedSet/with_scope/testRebuildTree.xml');
    }

    public function testRebuildTreeGivenNodeIdIsNotRoot()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Given node is not root node.');

        $this->treeAdapter->rebuild(5);
    }

    public function testIsValidTreeGivenNodeIdIsNotRoot()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Given node is not root node.');

        $this->treeAdapter->isValid(4);
    }

    public function testRebuildTreeGivenNodeIdDoesNotExists()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Node does not exists.');

        $this->treeAdapter->rebuild(999);
    }

    public function testIsValidTreeGivenNodeIdDoesNotExists()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Node does not exists.');

        $this->treeAdapter->isValid(555);
    }
}
