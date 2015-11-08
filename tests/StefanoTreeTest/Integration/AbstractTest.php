<?php
namespace StefanoTreeTest\Integration;

use StefanoTree\NestedSet as TreeAdapter;
use StefanoTreeTest\IntegrationTestCase;

abstract class AbstractTest
    extends IntegrationTestCase
{
    /**
     * @var TreeAdapter
     */
    protected $treeAdapter;
     
    protected function setUp() {
        $this->treeAdapter = $this->getTreeAdapter();
        
        parent::setUp();
    }

    protected function tearDown() {
        $this->treeAdapter = null;
        parent::tearDown();
    }

    /**
     * @return TreeAdapter
     */
    abstract protected function getTreeAdapter();

    protected function getDataSet() {
        return $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSet.xml');
    }    
    
    public function testClear() {
        $this->treeAdapter
             ->clear();

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testClear-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }
    
    public function testGetNode() {
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
        $this->assertNull($this->treeAdapter->getNode(123456789));
    }
    
    public function testAddNodeTargetNodeDoesNotExist() {
        //test
        $return = $this->treeAdapter
                       ->addNodePlacementBottom(123456789);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertFalse($return);
    }
    
    public function testAddNodePlacementBottom() {
        //test
        $return = $this->treeAdapter
                       ->addNodePlacementBottom(1);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertFalse($return);
        
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementBottom(12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementBottom(19, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);
    }
    
    public function testAddNodePlacementTop() {
        //test
        $return = $this->treeAdapter
                       ->addNodePlacementTop(1);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertFalse($return);
        
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementTop(16);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementTop(3, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);        
    }
    
    public function testAddNodePlacementChildBottom() {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildBottom(21);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementChildBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildBottom(4, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementChildBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);
    }    
    
    public function testAddNodePlacementChildTop() {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildTop(4);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementChildTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildTop(10, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementChildTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);
    }    
    
    public function testDeleteBranch() {
        //test 1
        $return = $this->treeAdapter
                       ->deleteBranch(1);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Cannot delete root node');
        $this->assertFalse($return);
        
        //test 2
        $return = $this->treeAdapter
                       ->deleteBranch(123456789);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Not Exist Branch');
        $this->assertFalse($return);
        
        //test 3
        $return = $this->treeAdapter
                       ->deleteBranch(6);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testDeleteBranch.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }
    
    public function testMoveUnmovableNode() {
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        
        //test 1
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(1, 12);
      
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Target node is inside source node');
        $this->assertFalse($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(10, 10);
        
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Target node and source node are same');
        $this->assertFalse($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(5, 123456);
        
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Target node does not exist');
        $this->assertFalse($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(123456, 6);
        
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Source node does not exist');
        $this->assertFalse($return);
    }
    
    public function testMoveNodePlacementBottom() {
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(11, 1);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Root node cannot have sibling');
        $this->assertFalse($return);
        
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(3, 2);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(14, 18);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(16, 7);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(14, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementBottom-3.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }
    
    public function testMoveNodePlacementTop() {
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(17, 1);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Root node cannot have sibling');
        $this->assertFalse($return);
        
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(3, 4);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(19, 12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(10, 18);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(21, 6);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementTop-3.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }    
    
    public function testMoveNodePlacementChildBottom() {
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(22, 18);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(9, 12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementChildBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(10, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementChildBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(21, 12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementChildBottom-3.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }    
    
    public function testMoveNodePlacementChildTop() {
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(21, 18);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/initDataSetWithIds.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(9, 21);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementChildTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(16, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementChildTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);

        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(18, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testMoveNodePlacementChildTop-3.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }     
    
    public function testGetPath() {
        //test
        $return = $this->treeAdapter
                       ->getPath(123456789);
        $this->assertNull($return);
        
        //test
        $return = $this->treeAdapter
                       ->getPath(6);
        $expected = array(
            array(
                'tree_traversal_id' => '1',
                'name' => null,
                'lft' => '1',
                'rgt' => '50',
                'parent_id' => '0',
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
        $this->assertSame($expected, $return);
        
        //test
        $return = $this->treeAdapter
                       ->getPath(6, 1);
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
        $this->assertSame($expected, $return);
        
        //test
        $return = $this->treeAdapter
                       ->getPath(6, 0, true);
        $expected = array(
            array(
                'tree_traversal_id' => '1',
                'name' => null,
                'lft' => '1',
                'rgt' => '50',
                'parent_id' => '0',
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
        $this->assertSame($expected, $return);
    }
    
    public function testGetDescedants() {
        //test
        $return = $this->treeAdapter
                       ->getDescendants(123456789);
        $this->assertNull($return);
        
        //test
        $return = $this->treeAdapter
                       ->getDescendants(1, 100000);
        $this->assertNull($return);
        
        //test whole branche
        $return = $this->treeAdapter
                       ->getDescendants(21);
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
        $this->assertSame($expected, $return);
        
        //test different start node
        $return = $this->treeAdapter
                       ->getDescendants(6, 3);
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
        $this->assertSame($expected, $return);
        
        //test custom levels
        $return = $this->treeAdapter
                       ->getDescendants(18, 0, 2);
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
        $this->assertSame($expected, $return);
        
        //test exclude node
        $return = $this->treeAdapter
                       ->getDescendants(12, 0, null, 21);
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
        $this->assertSame($expected, $return);
    }
    
    public function testGetChildren() {
        //test
        $return = $this->treeAdapter
                       ->getChildren(123456789);
        $this->assertNull($return);
        
        //test exclude node
        $return = $this->treeAdapter
                       ->getChildren(18);
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
    
    public function testUpdateNode() {
        //test
        $data = array(
            'name' => 'ahoj',
        );
        $this->treeAdapter
             ->updateNode(3, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testUpdateNode-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);        
        
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

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testUpdateNode-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);        
    }
}