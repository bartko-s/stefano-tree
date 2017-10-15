<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoTreeTest\IntegrationTestCase;

abstract class AbstractTest extends IntegrationTestCase
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
            case 'testCreateRootNode':
            case 'testCreateRootNodeWithCustomData':
            case 'testGetRootNodeRootDoesNotExist':
                return $this->createMySQLXMLDataSet(__DIR__.'/_files/NestedSet/initEmptyDataSet.xml');
            default:
                return $this->createMySQLXMLDataSet(__DIR__.'/_files/NestedSet/initDataSet.xml');
        }
    }

    public function testCreateRootNode()
    {
        $newId = $this->treeAdapter
            ->createRootNode();

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testCreateRootNode.xml');
        $this->assertEquals(1, $newId);
    }

    public function testCreateRootNodeWithCustomData()
    {
        $newId = $this->treeAdapter
            ->createRootNode(array('name' => 'This is root node'));

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testCreateRootNodeWithCustomData.xml');
        $this->assertEquals(1, $newId);
    }

    public function testCreateRootRootAlreadyExist()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Root node already exist');

        $this->treeAdapter
             ->createRootNode();
        $this->treeAdapter
            ->createRootNode();
    }

    public function testGetNode()
    {
        $expectedNodeData = array(
            'tree_traversal_id' => '12',
            'name' => null,
            'lft' => '18',
            'rgt' => '29',
            'parent_id' => '6',
            'level' => '3',
        );

        $nodeData = $this->treeAdapter
                         ->getNode(12);

        $this->assertEquals($expectedNodeData, $nodeData);
    }

    public function testGetNodeNodeDoesNotExist()
    {
        $this->assertNull($this->treeAdapter->getNode(123456789));
    }

    public function testAddNodeTargetNodeDoesNotExist()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Target Node does not exists.');

        try {
            $this->treeAdapter
                ->addNode(123456789, array(), TreeAdapter::PLACEMENT_BOTTOM);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testCreateNodePlacementStrategyDoesNotExists()
    {
        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown placement "unknown-placement"');

        $this->treeAdapter
            ->addNode(1, array(), 'unknown-placement');
    }

    public function testAddNodePlacementBottomTargetNodeIsRoot()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot create node. Target node is root. Root node cannot have sibling.');

        try {
            $this->treeAdapter
                ->addNode(1, array(), TreeAdapter::PLACEMENT_BOTTOM);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testAddNodePlacementTopTargetNodeIsRoot()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot create node. Target node is root. Root node cannot have sibling.');

        try {
            $this->treeAdapter
                ->addNode(1, array(), TreeAdapter::PLACEMENT_TOP);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testAddNodePlacementBottom()
    {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(12, array(), TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementBottom-1.xml');
        $this->assertEquals(26, $lastGeneratedValue);

        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );

        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(19, $data, TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementBottom-2.xml');
        $this->assertEquals(27, $lastGeneratedValue);
    }

    public function testAddNodePlacementTop()
    {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(16, array(), TreeAdapter::PLACEMENT_TOP);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementTop-1.xml');
        $this->assertEquals(26, $lastGeneratedValue);

        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(3, $data, TreeAdapter::PLACEMENT_TOP);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementTop-2.xml');
        $this->assertEquals(27, $lastGeneratedValue);
    }

    public function testAddNodePlacementChildBottom()
    {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(21, array(), TreeAdapter::PLACEMENT_CHILD_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementChildBottom-1.xml');
        $this->assertEquals(26, $lastGeneratedValue);

        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(4, $data, TreeAdapter::PLACEMENT_CHILD_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementChildBottom-2.xml');
        $this->assertEquals(27, $lastGeneratedValue);
    }

    public function testAddNodePlacementChildTopDefaultPlacement()
    {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(4);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementChildTop-1.xml');
        $this->assertEquals(26, $lastGeneratedValue);

        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNode(10, $data);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testAddNodePlacementChildTop-2.xml');
        $this->assertEquals(27, $lastGeneratedValue);
    }

    public function testDeleteBranchDoesNotExist()
    {
        $this->treeAdapter
            ->deleteBranch(123456789);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
    }

    public function testDeleteBranch()
    {
        $this->treeAdapter
                       ->deleteBranch(6);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testDeleteBranch.xml');
    }

    public function testMoveNodeCannotMoveTargetNodeIsInsideSourceBranch()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot move. Target node is inside source branch.');

        try {
            $this->treeAdapter
                ->moveNode(1, 12);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testMoveNodeCannotMoveTargetAndSourceNodeAreEqual()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot move. Source node and Target node are equal.');

        try {
            $this->treeAdapter
                ->moveNode(10, 10);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testMoveNodeCannotMoveTargetNodeDoesNotExist()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot move. Target node does not exists.');

        try {
            $this->treeAdapter
                ->moveNode(5, 123456);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testMoveNodeCannotMoveSourceNodeDoesNotExist()
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot move. Source node does not exists.');

        try {
            $this->treeAdapter
                ->moveNode(123456, 6);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testMoveNodePlacementStrategyDoesNotExists()
    {
        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown placement "unknown-placement"');

        $this->treeAdapter
            ->moveNode(11, 1, 'unknown-placement');
    }

    public function placementDataProvider()
    {
        return [
            [\StefanoTree\TreeInterface::PLACEMENT_TOP],
            [\StefanoTree\TreeInterface::PLACEMENT_BOTTOM],
        ];
    }

    /**
     * @dataProvider placementDataProvider
     *
     * @param string $placement
     *
     * @throws \Exception
     */
    public function testMoveNodeCannotCreateSiblingNodeAtRootNode(string $placement)
    {
        $this->expectException(\StefanoTree\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot move. Target node is root. Root node cannot have sibling.');

        try {
            $this->treeAdapter
                ->moveNode(11, 1, $placement);
        } catch (\Exception $e) {
            $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');
            throw $e;
        }
    }

    public function testMoveNodePlacementBottom()
    {
        //test source node is already at required position
        $this->treeAdapter
                       ->moveNode(3, 2, TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');

        //test
        $this->treeAdapter
                       ->moveNode(14, 18, TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementBottom-1.xml');

        //test
        $this->treeAdapter
                       ->moveNode(16, 7, TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementBottom-2.xml');

        //test
        $this->treeAdapter
                       ->moveNode(14, 3, TreeAdapter::PLACEMENT_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementBottom-3.xml');
    }

    public function testMoveNodePlacementTop()
    {
        //test source node is already at required position
        $this->treeAdapter
                       ->moveNode(3, 4, TreeAdapter::PLACEMENT_TOP);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');

        //test
        $this->treeAdapter
                       ->moveNode(19, 12, TreeAdapter::PLACEMENT_TOP);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementTop-1.xml');

        //test
        $this->treeAdapter
                       ->moveNode(10, 18, TreeAdapter::PLACEMENT_TOP);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementTop-2.xml');

        //test
        $this->treeAdapter
                       ->moveNode(21, 6, TreeAdapter::PLACEMENT_TOP);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementTop-3.xml');
    }

    public function testMoveNodePlacementChildBottom()
    {
        //test source node is already at required position
        $this->treeAdapter
                       ->moveNode(22, 18, TreeAdapter::PLACEMENT_CHILD_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');

        //test
        $this->treeAdapter
                       ->moveNode(9, 12, TreeAdapter::PLACEMENT_CHILD_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementChildBottom-1.xml');

        //test
        $this->treeAdapter
                       ->moveNode(10, 3, TreeAdapter::PLACEMENT_CHILD_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementChildBottom-2.xml');

        //test
        $this->treeAdapter
                       ->moveNode(21, 12, TreeAdapter::PLACEMENT_CHILD_BOTTOM);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementChildBottom-3.xml');
    }

    public function testMoveNodePlacementChildTopDefaultPlacement()
    {
        //test source node is already at required position
        $this->treeAdapter
                       ->moveNode(21, 18);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/initDataSetWithIds.xml');

        //test
        $this->treeAdapter
                       ->moveNode(9, 21);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementChildTop-1.xml');

        //test
        $this->treeAdapter
                       ->moveNode(16, 3);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementChildTop-2.xml');

        //test
        $this->treeAdapter
               ->moveNode(18, 3);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testMoveNodePlacementChildTop-3.xml');
    }

    public function testGetAncestorsReturnEmptyArrayIfNodeDoesNotExist()
    {
        $return = $this->treeAdapter
            ->getAncestorsQueryBuilder()
            ->get(123456789);

        $this->assertEquals(array(), $return);
    }

    public function testGetAncestorsReturnEmptyArrayIfNodeExistButHasNoPath()
    {
        $return = $this->treeAdapter
            ->getAncestorsQueryBuilder()
            ->excludeLastNLevel(1)
            ->get(1);

        $this->assertEquals(array(), $return);
    }

    public function testGetAncestor()
    {
        //test
        $return = $this->treeAdapter
                       ->getAncestorsQueryBuilder()
                       ->get(6);

        $expected = array(
            array(
                'tree_traversal_id' => '1',
                'name' => null,
                'lft' => '1',
                'rgt' => '50',
                'parent_id' => null,
                'level' => '0',
            ),
            array(
                'tree_traversal_id' => '3',
                'name' => null,
                'lft' => '16',
                'rgt' => '35',
                'parent_id' => '1',
                'level' => '1',
            ),
            array(
                'tree_traversal_id' => '6',
                'name' => null,
                'lft' => '17',
                'rgt' => '32',
                'parent_id' => '3',
                'level' => '2',
            ),
        );
        $this->assertEquals($expected, $return);

        //test
        $return = $this->treeAdapter
                       ->getAncestorsQueryBuilder()
                       ->excludeFirstNLevel(1)
                       ->get(6);

        $expected = array(
            array(
                'tree_traversal_id' => '3',
                'name' => null,
                'lft' => '16',
                'rgt' => '35',
                'parent_id' => '1',
                'level' => '1',
            ),
            array(
                'tree_traversal_id' => '6',
                'name' => null,
                'lft' => '17',
                'rgt' => '32',
                'parent_id' => '3',
                'level' => '2',
            ),
        );
        $this->assertEquals($expected, $return);

        //test
        $return = $this->treeAdapter
                       ->getAncestorsQueryBuilder()
                       ->excludeLastNLevel(1)
                       ->get(6);

        $expected = array(
            array(
                'tree_traversal_id' => '1',
                'name' => null,
                'lft' => '1',
                'rgt' => '50',
                'parent_id' => null,
                'level' => '0',
            ),
            array(
                'tree_traversal_id' => '3',
                'name' => null,
                'lft' => '16',
                'rgt' => '35',
                'parent_id' => '1',
                'level' => '1',
            ),
        );
        $this->assertEquals($expected, $return);
    }

    public function testGetDescendantsReturnEmptyArrayIfNodeDoesNotExist()
    {
        $return = $this->treeAdapter
            ->getDescendantsQueryBuilder()
            ->get(123456789);
        $this->assertEquals(array(), $return);
    }

    public function testGetDescendantsReturnEmptyArrayNodeDoesNotHaveDescendants()
    {
        $return = $this->treeAdapter
            ->getDescendantsQueryBuilder()
            ->excludeFirstNLevel(1)
            ->get(8);

        $this->assertEquals(array(), $return);
    }

    public function testGetDescendants()
    {
        //test whole branch
        $return = $this->treeAdapter
                       ->getDescendantsQueryBuilder()
                       ->get(21);

        $expected = array(
            array(
                'tree_traversal_id' => '21',
                'name' => null,
                'lft' => '20',
                'rgt' => '25',
                'parent_id' => '18',
                'level' => '5',
            ),
            array(
                'tree_traversal_id' => '24',
                'name' => null,
                'lft' => '21',
                'rgt' => '22',
                'parent_id' => '21',
                'level' => '6',
            ),
            array(
                'tree_traversal_id' => '25',
                'name' => null,
                'lft' => '23',
                'rgt' => '24',
                'parent_id' => '21',
                'level' => '6',
            ),
        );
        $this->assertEquals($expected, $return);

        //test exclude fist 3 levels
        $return = $this->treeAdapter
                       ->getDescendantsQueryBuilder()
                       ->excludeFirstNLevel(3)
                       ->get(6);

        $expected = array(
            array(
                'tree_traversal_id' => '21',
                'name' => null,
                'lft' => '20',
                'rgt' => '25',
                'parent_id' => '18',
                'level' => '5',
            ),
            array(
                'tree_traversal_id' => '24',
                'name' => null,
                'lft' => '21',
                'rgt' => '22',
                'parent_id' => '21',
                'level' => '6',
            ),
            array(
                'tree_traversal_id' => '25',
                'name' => null,
                'lft' => '23',
                'rgt' => '24',
                'parent_id' => '21',
                'level' => '6',
            ),
            array(
                'tree_traversal_id' => '22',
                'name' => null,
                'lft' => '26',
                'rgt' => '27',
                'parent_id' => '18',
                'level' => '5',
            ),
        );
        $this->assertEquals($expected, $return);

        //test limit depth
        $return = $this->treeAdapter
                       ->getDescendantsQueryBuilder()
                       ->levelLimit(2)
                       ->get(18);

        $expected = array(
            array(
                'tree_traversal_id' => '18',
                'name' => null,
                'lft' => '19',
                'rgt' => '28',
                'parent_id' => '12',
                'level' => '4',
            ),
            array(
                'tree_traversal_id' => '21',
                'name' => null,
                'lft' => '20',
                'rgt' => '25',
                'parent_id' => '18',
                'level' => '5',
            ),
            array(
                'tree_traversal_id' => '22',
                'name' => null,
                'lft' => '26',
                'rgt' => '27',
                'parent_id' => '18',
                'level' => '5',
            ),
        );
        $this->assertEquals($expected, $return);

        //test exclude node
        $return = $this->treeAdapter
                       ->getDescendantsQueryBuilder()
                       ->excludeBranch(21)
                       ->get(12);

        $expected = array(
            array(
                'tree_traversal_id' => '12',
                'name' => null,
                'lft' => '18',
                'rgt' => '29',
                'parent_id' => '6',
                'level' => '3',
            ),
            array(
                'tree_traversal_id' => '18',
                'name' => null,
                'lft' => '19',
                'rgt' => '28',
                'parent_id' => '12',
                'level' => '4',
            ),
            array(
                'tree_traversal_id' => '22',
                'name' => null,
                'lft' => '26',
                'rgt' => '27',
                'parent_id' => '18',
                'level' => '5',
            ),
        );
        $this->assertEquals($expected, $return);
    }

    public function testGetChildrenReturnEmptyArrayIfNodeDoesNotExist()
    {
        $return = $this->treeAdapter
            ->getDescendantsQueryBuilder()
            ->excludeFirstNLevel(1)
            ->levelLimit(1)
            ->get(123456789);

        $this->assertEquals(array(), $return);
    }

    public function testGetChildrenReturnEmptyArrayIfNodeDoesNotHaveChildren()
    {
        $return = $this->treeAdapter
            ->getDescendantsQueryBuilder()
            ->excludeFirstNLevel(1)
            ->levelLimit(1)
            ->get(8);

        $this->assertEquals(array(), $return);
    }

    public function testGetChildren()
    {
        //test exclude node
        $return = $this->treeAdapter
                       ->getDescendantsQueryBuilder()
                       ->levelLimit(1)
                       ->excludeFirstNLevel(1)
                       ->get(18);

        $expected = array(
            array(
                'tree_traversal_id' => '21',
                'name' => null,
                'lft' => '20',
                'rgt' => '25',
                'parent_id' => '18',
                'level' => '5',
            ),
            array(
                'tree_traversal_id' => '22',
                'name' => null,
                'lft' => '26',
                'rgt' => '27',
                'parent_id' => '18',
                'level' => '5',
            ),
        );
        $this->assertEquals($expected, $return);
    }

    public function testUpdateNode()
    {
        //test
        $data = array(
            'name' => 'ahoj',
        );
        $this->treeAdapter
             ->updateNode(3, $data);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testUpdateNode-1.xml');

        //test
        $data = array(
            'name' => 'ahoj',
            'lft' => '123456',
            'rgt' => '123456',
            'tree_traversal_id' => '123456',
            'level' => '123456',
            'parent_id' => '123456',
        );
        $this->treeAdapter
             ->updateNode(3, $data);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/NestedSet/testUpdateNode-1.xml');
    }

    public function testGetRootNodeRootDoesNotExist()
    {
        $return = $this->treeAdapter
            ->getRootNode();

        $this->assertEquals(array(), $return);
    }

    public function testGetRootNode()
    {
        $return = $this->treeAdapter
            ->getRootNode();

        $expected = array(
            'tree_traversal_id' => '1',
            'name' => '',
            'lft' => '1',
            'rgt' => '50',
            'parent_id' => null,
            'level' => '0',
        );
        $this->assertEquals($expected, $return);
    }
}
