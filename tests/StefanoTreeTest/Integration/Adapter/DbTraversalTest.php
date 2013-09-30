<?php
namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\Adapter\DbTraversal as TreeAdapter;
use StefanoDb\Adapter\Adapter as DbAdapter;

class DbTraversalTest
    extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var TreeAdapter
     */
    protected $treeAdapter;
    
    /**
     * @var DbAdapter
     */
    protected $dbAdapter;
 
    protected function setUp() {
        $this->setDbConnectionAndCreateTable();
        
        $treeAdapter = new TreeAdapter(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
            'dbAdapter' => $this->dbAdapter,
        ));
        $this->treeAdapter = $treeAdapter;
        
        parent::setUp();
    }
    
    protected function getConnection() {
        $adapter    = strtolower(TEST_STEFANO_DB_ADAPTER);
        $hostname   = TEST_STEFANO_DB_HOSTNAME;
        $dbName     = TEST_STEFANO_DB_DB_NAME;
        $user       = TEST_STEFANO_DB_USER;
        $password   = TEST_STEFANO_DB_PASSWORD;
        
        $pdo = new \PDO($adapter . ':host=' . $hostname . ';dbname=' . $dbName, $user, $password);
        return $this->createDefaultDBConnection($pdo);
    }
    
    protected function setDbConnectionAndCreateTable() {
        $dbAdapter = new DbAdapter(array(
            'driver' => 'Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER),
            'hostname' => TEST_STEFANO_DB_HOSTNAME,
            'database' => TEST_STEFANO_DB_DB_NAME,
            'username' => TEST_STEFANO_DB_USER,
            'password' => TEST_STEFANO_DB_PASSWORD
        ));
        $this->dbAdapter = $dbAdapter;
        
        $sql =  'CREATE TABLE IF NOT EXISTS `tree_traversal` (
                    `tree_traversal_id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                    `lft` int(11) NOT NULL,
                    `rgt` int(11) NOT NULL,
                    `parent_id` int(11) DEFAULT NULL,
                    `level` int(11) DEFAULT NULL,
                    PRIMARY KEY (`tree_traversal_id`),
                    KEY `parent_id` (`parent_id`),
                    KEY `level` (`level`),
                    KEY `lft` (`lft`),
                    KEY `rgt` (`rgt`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin';
        
        $dbAdapter->query($sql, DbAdapter::QUERY_MODE_EXECUTE);
    }

    protected function getDataSet() {
        return $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/initDataSet.xml');
    }    
    
    public function testClear() {
        //test
        $this->treeAdapter
             ->clear();

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testClear-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        
        //test
        $data = array(
            'name' => 'ahoj',
        );
        $this->treeAdapter
             ->clear($data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testClear-2.xml');
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
    
    public function testGetNodeInfo() {
        $this->assertNull($this->treeAdapter->getNodeInfo(123456789));
        
        $nodeInfo = $this->treeAdapter
                         ->getNodeInfo(11);
        
        $this->assertEquals(11, $nodeInfo->getId());
        $this->assertEquals(5, $nodeInfo->getParentId());
        $this->assertEquals(3, $nodeInfo->getLevel());
        $this->assertEquals(12, $nodeInfo->getLeft());
        $this->assertEquals(13, $nodeInfo->getRight());
    }
    
    public function testAddNodeTargetNodeDoesNotExist() {
        //test
        $return = $this->treeAdapter
                       ->addNodePlacementBottom(123456789);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->getDataSet();
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertFalse($return);
    }
    
    public function testAddNodePlacementBottom() {
        //test
        $return = $this->treeAdapter
                       ->addNodePlacementBottom(1);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->getDataSet();
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertFalse($return);
        
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementBottom(12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementBottom(19, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);
    }
    
    public function testAddNodePlacementTop() {
        //test
        $return = $this->treeAdapter
                       ->addNodePlacementTop(1);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->getDataSet();
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertFalse($return);
        
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementTop(16);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementTop(3, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);        
    }
    
    public function testAddNodePlacementChildBottom() {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildBottom(21);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementChildBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildBottom(4, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementChildBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);
    }    
    
    public function testAddNodePlacementChildTop() {
        //test 1
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildTop(4);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementChildTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(26, $lastGeneratedValue);
        
        //test 2 with data
        $data = array(
            'name' => 'ahoj',
        );
        $lastGeneratedValue = $this->treeAdapter
                                   ->addNodePlacementChildTop(10, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testAddNodePlacementChildTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(27, $lastGeneratedValue);
    }    
    
    public function testDeleteBranch() {
        //test 1
        $return = $this->treeAdapter
                       ->deleteBranch(1);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));        
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Cannot delete root node');
        $this->assertFalse($return);
        
        //test 2
        $return = $this->treeAdapter
                       ->deleteBranch(123456789);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));        
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Not Exist Branch');
        $this->assertFalse($return);
        
        //test 3
        $return = $this->treeAdapter
                       ->deleteBranch(6);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testDeleteBranch.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }
    
    public function testMoveUnmovableNode() {
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        
        //test 1
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(1, 12);
      
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Target node is inside source node');
        $this->assertFalse($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(10, 10);
        
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Target node and source node are same');
        $this->assertFalse($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(5, 123456);
        
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Target node does not exist');
        $this->assertFalse($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(123456, 6);
        
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Source node does not exist');
        $this->assertFalse($return);
    }
    
    public function testMoveNodePlacementBottom() {
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(11, 1);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Root node cannot have sibling');
        $this->assertFalse($return);
        
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(3, 2);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(14, 18);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(16, 7);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementBottom(14, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementBottom-3.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }
    
    public function testMoveNodePlacementTop() {
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(17, 1);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Root node cannot have sibling');
        $this->assertFalse($return);
        
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(3, 4);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(19, 12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(10, 18);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementTop(21, 6);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementTop-3.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }    
    
    public function testMoveNodePlacementChildBottom() {
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(22, 18);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(9, 12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementChildBottom-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(10, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementChildBottom-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildBottom(21, 12);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementChildBottom-3.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
    }    
    
    public function testMoveNodePlacementChildTop() {
        //test 
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(21, 18);
      
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $this->assertDataSetsEqual($this->getDataSet(), $dataSet, 'Source node is in required position');
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(9, 21);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementChildTop-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);
        
        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(16, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementChildTop-2.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertTrue($return);

        //test
        $return = $this->treeAdapter
                       ->moveNodePlacementChildTop(18, 3);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testMoveNodePlacementChildTop-3.xml');
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
        $return = $this->treeAdapter
                       ->updateNode(3, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testUpdateNode-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);        
        
        //test
        $data = array(
            'name' => 'ahoj',
            $this->treeAdapter->getLeftColumnName() => '123456',
            $this->treeAdapter->getRightColumnName() => '123456',
            $this->treeAdapter->getIdColumnName() => '123456',
            $this->treeAdapter->getLevelColumnName() => '123456',
            $this->treeAdapter->getParentIdColumnName() => '123456',
        );
        $return = $this->treeAdapter
                       ->updateNode(3, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/DbTraversal/testUpdateNode-1.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);        
    }
    
    public function testSetGetDefaultDbSelect() {
        $treeAdapter = $this->treeAdapter;
        
        $select = $treeAdapter->getDefaultDbSelect();
        $select->join('jointable', 'jointable.col = ' . $treeAdapter->getTableName() . '.col');
        $return = $treeAdapter->setDefaultDbSelect($select);
        
        $expected = $select->getSqlString($treeAdapter->getDbAdapter()->getPlatform());
        $actual = $treeAdapter->getDefaultDbSelect()
                              ->getSqlString($treeAdapter->getDbAdapter()->getPlatform());
        $this->assertEquals($expected, $actual);
        $this->assertInstanceOf('\StefanoTree\Adapter\DbTraversal', $return);
    }
}