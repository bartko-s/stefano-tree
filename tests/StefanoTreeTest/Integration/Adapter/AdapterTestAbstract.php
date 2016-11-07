<?php
namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTreeTest\IntegrationTestCase;

abstract class AdapterTestAbstract
    extends IntegrationTestCase
{
    /**
     * @var TreeAdapterInterface
     */
    protected $adapter;

    protected function setUp()
    {
        $this->adapter = $this->getAdapter();

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->adapter = null;
        parent::tearDown();
    }

    /**
     * @return TreeAdapterInterface
     */
    abstract protected function getAdapter();

    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/initDataSet.xml');
    }

    public function testLockTreeDoesNotFail()
    {
        $this->adapter
            ->lockTree(null);
    }

    public function testDbTransactionDoesNotFail()
    {
        $this->adapter
            ->beginTransaction();
        $this->adapter
            ->commitTransaction();

        $this->adapter
            ->beginTransaction();
        $this->adapter
            ->rollbackTransaction();
    }

    public function testUpdateData()
    {
        $this->adapter
            ->update(2, array('name' => 'changed'));
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testUpdateData.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdateDataDoesNotChangeMetadata()
    {
        $data = array(
            'name' => 'changed',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'level' => 'd',
        );

        $this->adapter
            ->update(2, $data);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testUpdateData.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertData()
    {
        $nodeInfo = new NodeInfo(null, 6, 100, 1000, 1001, null);

        $this->adapter
            ->insert($nodeInfo, array('name' => 'some-name'));
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testInsertData.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertDataDoesNotChangeMetadata()
    {
        $nodeInfo = new NodeInfo(null, 6, 100, 1000, 1001, null);

        $data = array(
            'name' => 'some-name',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'level' => 'd',
        );

        $this->adapter
            ->insert($nodeInfo, $data);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testInsertData.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeleteBranch()
    {
        $this->adapter
            ->delete(16, 35);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testDeleteBranch.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testMoveLeftIndexes()
    {
        $this->adapter
            ->moveLeftIndexes(12, 500);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testMoveLeftIndexes.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testMoveRightIndexes()
    {
        $this->adapter
            ->moveRightIndexes(15, 500);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testMoveRightIndexes.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdateParentId()
    {
        $this->adapter
            ->updateParentId(3, 22);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testUpdateParentId.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdateLevels()
    {
        $this->adapter
            ->updateLevels(16, 35, 500);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testUpdateLevels.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testMoveBranch()
    {
        $this->adapter
            ->moveBranch(17, 32, 500);
        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testMoveBranch.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testGetRoots()
    {
        $roots = $this->adapter
            ->getRoots();

        $expected = include __DIR__ . '/_files/adapter/testGetRoots.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetRoot()
    {
        $roots = $this->adapter
            ->getRoot();

        $expected = include __DIR__ . '/_files/adapter/testGetRoot.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetNodeReturnNullIfNodeDoesNotExist()
    {
        $node = $this->adapter
            ->getNode(1000000);
        $this->assertNull($node);
    }

    public function testGetNode()
    {
        $node = $this->adapter
            ->getNode(11);

        $expected = include __DIR__ . '/_files/adapter/testGetNode.php';
        $this->assertEquals($expected, $node);
    }

    public function testGetNodeInfoReturnNullIfNodeInfoDoesNotExist()
    {
        $nodeInfo = $this->adapter
            ->getNodeInfo(10000000);
        $this->assertNull($nodeInfo);
    }

    public function testGetNodeInfo()
    {
        $nodeInfo = $this->adapter
            ->getNodeInfo(10);

        $this->assertEquals($nodeInfo->getId(), 10);
        $this->assertEquals($nodeInfo->getParentId(), 5);
        $this->assertEquals($nodeInfo->getLeft(), 4);
        $this->assertEquals($nodeInfo->getRight(), 11);
        $this->assertEquals($nodeInfo->getLevel(), 3);
    }

    public function testGetChildrenNodeInfoReturnEmptyArrayIfNodeDoesNotHaveChildrenNodes()
    {
        $nodeInfo = $this->adapter
            ->getChildrenNodeInfo(7);

        $this->assertEquals(array(), $nodeInfo);
    }

    public function testGetChildrenNodeInfo()
    {
        $nodeInfo = $this->adapter
            ->getChildrenNodeInfo(4);

        $this->assertCount(2, $nodeInfo);

        // check first node info
        $this->assertEquals($nodeInfo[0]->getId(), 8);
        $this->assertEquals($nodeInfo[0]->getParentId(), 4);
        $this->assertEquals($nodeInfo[0]->getLeft(), 37);
        $this->assertEquals($nodeInfo[0]->getRight(), 38);
        $this->assertEquals($nodeInfo[0]->getLevel(), 2);

        // check second node info
        $this->assertEquals($nodeInfo[1]->getId(), 9);
        $this->assertEquals($nodeInfo[1]->getParentId(), 4);
        $this->assertEquals($nodeInfo[1]->getLeft(), 39);
        $this->assertEquals($nodeInfo[1]->getRight(), 48);
        $this->assertEquals($nodeInfo[1]->getLevel(), 2);
    }

    public function testUpdateNodeMetadata()
    {
        $nodeInfo = new NodeInfo(2, 100, 101, 102, 103, null);

        $this->adapter
            ->updateNodeMetadata($nodeInfo);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/adapter/testUpdateNodeMetadata.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testGetPathReturnNullEmptyIfNodeDoestNotExist() // todo return array
    {
        $path = $this->adapter
            ->getPath(1000);

        $this->assertNull($path);
    }

    public function testGetPath()
    {
        $path = $this->adapter
            ->getPath(10);

        $this->assertCount(4, $path);

        $expected = include __DIR__ . '/_files/adapter/testGetPath.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetPathFromLevel()
    {
        $path = $this->adapter
            ->getPath(10, 2);

        $this->assertCount(2, $path);

        $expected = include __DIR__ . '/_files/adapter/testGetPathStartFromLevel.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetPathExcludeLastNode()
    {
        $path = $this->adapter
            ->getPath(10, 0, True);

        $this->assertCount(3, $path);

        $expected = include __DIR__ . '/_files/adapter/testGetPathExcludeLastNode.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetDescendantsReturnNullIfNodeDoesNotExist() // todo return array
    {
        $nodes = $this->adapter
            ->getDescendants(1000);

        $this->assertNull($nodes);
    }

    public function testGetDescendants()
    {
        $nodes = $this->adapter
            ->getDescendants();

        $this->assertCount(25, $nodes);

        $expected = include __DIR__ . '/_files/adapter/testGetDescendants.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsDefinedNodeId()
    {
        $nodes = $this->adapter
            ->getDescendants(6);

        $this->assertCount(8, $nodes);

        $expected = include __DIR__ . '/_files/adapter/testGetDescendantsDefinedNodeId.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsFromLevel()
    {
        $nodes = $this->adapter
            ->getDescendants(6, 2);

        $this->assertCount(5, $nodes);

        $expected = include __DIR__ . '/_files/adapter/testGetDescendantsFromLevel.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsFixLevels()
    {
        $nodes = $this->adapter
            ->getDescendants(6, 2, 2);

        $this->assertCount(3, $nodes);

        $expected = include __DIR__ . '/_files/adapter/testGetDescendantsFixLevels.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsExcludeBranch()
    {
        $nodes = $this->adapter
            ->getDescendants(1, 0, null, 9);

        $this->assertCount(20, $nodes);

        $expected = include __DIR__ . '/_files/adapter/testGetDescendantsExcludeBranch.php';
        $this->assertEquals($expected, $nodes);
    }
}
