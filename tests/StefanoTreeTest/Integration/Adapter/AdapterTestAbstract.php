<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTreeTest\IntegrationTestCase;

abstract class AdapterTestAbstract extends IntegrationTestCase
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
        return $this->createMySQLXMLDataSet(__DIR__.'/_files/adapter/initDataSet.xml');
    }

    public function testLockTreeDoesNotFail()
    {
        $this->adapter
            ->lockTree();
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

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateData.xml');
    }

    public function testUpdateDataDoesNotChangeMetadata()
    {
        $data = array(
            'name' => 'changed',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'tree_traversal_id' => 1596,
            'level' => 'd',
        );

        $this->adapter
            ->update(2, $data);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateData.xml');
    }

    public function testInsertData()
    {
        $nodeInfo = new NodeInfo(null, 6, 100, 1000, 1001, null);

        $generatedId = $this->adapter
            ->insert($nodeInfo, array('name' => 'some-name'));

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testInsertData.xml');
        $this->assertEquals(26, $generatedId);
    }

    public function testInsertDataUserDefinedId()
    {
        $uuid = 753;
        $nodeInfo = new NodeInfo(null, 6, 100, 1000, 1001, null);

        $generatedId = $this->adapter
            ->insert($nodeInfo, array('name' => 'some-name', 'tree_traversal_id' => $uuid));

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testInsertDataUserDefinedId.xml');
        $this->assertEquals($uuid, $generatedId);
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

        $generatedId = $this->adapter
            ->insert($nodeInfo, $data);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testInsertData.xml');
        $this->assertEquals(26, $generatedId);
    }

    public function testDeleteBranch()
    {
        $this->adapter
            ->delete(3);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testDeleteBranch.xml');
    }

    public function testMoveLeftIndexes()
    {
        $this->adapter
            ->moveLeftIndexes(12, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testMoveLeftIndexes.xml');
    }

    public function testMoveRightIndexes()
    {
        $this->adapter
            ->moveRightIndexes(15, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testMoveRightIndexes.xml');
    }

    public function testUpdateParentId()
    {
        $this->adapter
            ->updateParentId(3, 22);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateParentId.xml');
    }

    public function testUpdateLevels()
    {
        $this->adapter
            ->updateLevels(16, 35, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateLevels.xml');
    }

    public function testMoveBranch()
    {
        $this->adapter
            ->moveBranch(17, 32, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testMoveBranch.xml');
    }

    public function testGetRoots()
    {
        $roots = $this->adapter
            ->getRoots();

        $expected = include __DIR__.'/_files/adapter/testGetRoots.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetRoot()
    {
        $roots = $this->adapter
            ->getRoot();

        $expected = include __DIR__.'/_files/adapter/testGetRoot.php';
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

        $expected = include __DIR__.'/_files/adapter/testGetNode.php';
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

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateNodeMetadata.xml');
    }

    public function testGetAncestorsReturnEmptyArrayIfNodeDoestNotExist()
    {
        $path = $this->adapter
            ->getAncestors(1000);

        $this->assertEquals(array(), $path);
    }

    public function testGetAncestors()
    {
        $path = $this->adapter
            ->getAncestors(10);

        $this->assertCount(4, $path);

        $expected = include __DIR__.'/_files/adapter/testGetAncestors.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetAncestorsFromLevel()
    {
        $path = $this->adapter
            ->getAncestors(10, 2);

        $this->assertCount(2, $path);

        $expected = include __DIR__.'/_files/adapter/testGetAncestorsStartFromLevel.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetAncestorsExcludeLastNode()
    {
        // test exclude last node
        $path = $this->adapter
            ->getAncestors(10, 0, 1);

        $this->assertCount(3, $path);
        $expected = include __DIR__.'/_files/adapter/testGetAncestorsExcludeLastNode.php';
        $this->assertEquals($expected, $path);

        // test exclude last two node
        $path = $this->adapter
            ->getAncestors(10, 0, 2);

        $this->assertCount(2, $path);
        $expected = include __DIR__.'/_files/adapter/testGetAncestorsExcludeTwoLastNode.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetDescendantsReturnEmptyArrayIfNodeDoesNotExist()
    {
        $nodes = $this->adapter
            ->getDescendants(1000);

        $this->assertEquals(array(), $nodes);
    }

    public function testGetDescendants()
    {
        $nodes = $this->adapter
            ->getDescendants(1);

        $this->assertCount(25, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendants.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsDefinedNodeId()
    {
        $nodes = $this->adapter
            ->getDescendants(6);

        $this->assertCount(8, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsDefinedNodeId.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsFromLevel()
    {
        $nodes = $this->adapter
            ->getDescendants(6, 2);

        $this->assertCount(5, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsFromLevel.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsFixLevels()
    {
        $nodes = $this->adapter
            ->getDescendants(6, 2, 2);

        $this->assertCount(3, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsFixLevels.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsExcludeBranch()
    {
        $nodes = $this->adapter
            ->getDescendants(1, 0, null, 9);

        $this->assertCount(20, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsExcludeBranch.php';
        $this->assertEquals($expected, $nodes);
    }
}
