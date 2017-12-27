<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTreeTest\IntegrationTestCase;

abstract class AdapterWithScopeTestAbstract extends IntegrationTestCase
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
        return $this->createMySQLXMLDataSet(__DIR__.'/_files/adapter/with_scope/initDataSet.xml');
    }

    public function testUpdateDataDoesNotChangeMetadata()
    {
        $data = array(
            'name' => 'changed',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'level' => 'd',
            'scope' => 'e',
        );

        $this->adapter
            ->update(2, $data);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testUpdateData.xml');
    }

    public function testInsertDataDoesNotChangeMetadata()
    {
        $nodeInfo = new NodeInfo(null, 6, 1001, 1002, 1003, 1004);

        $data = array(
            'name' => 'some-name',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'level' => 'd',
            'scope' => 'e',
        );

        $this->adapter
            ->insert($nodeInfo, $data);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testInsertData.xml');
    }

    public function testDeleteBranch()
    {
        $this->adapter
            ->delete(2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testDeleteBranch.xml');
    }

    public function testMoveLeftIndexes()
    {
        $this->adapter
            ->moveLeftIndexes(3, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testMoveLeftIndexes.xml');
    }

    public function testMoveRightIndexes()
    {
        $this->adapter
            ->moveRightIndexes(4, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testMoveRightIndexes.xml');
    }

    public function testUpdateLevels()
    {
        $this->adapter
            ->updateLevels(2, 9, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testUpdateLevels.xml');
    }

    public function testMoveBranch()
    {
        $this->adapter
            ->moveBranch(2, 9, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testMoveBranch.xml');
    }

    public function testGetRoots()
    {
        $roots = $this->adapter
            ->getRoots();

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetRoots.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetRoot()
    {
        $roots = $this->adapter
            ->getRoot(2);

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetRoot.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetNodeInfo()
    {
        $nodeInfo = $this->adapter
            ->getNodeInfo(8);

        $this->assertEquals($nodeInfo->getId(), 8);
        $this->assertEquals($nodeInfo->getParentId(), 7);
        $this->assertEquals($nodeInfo->getLeft(), 3);
        $this->assertEquals($nodeInfo->getRight(), 8);
        $this->assertEquals($nodeInfo->getLevel(), 2);
        $this->assertEquals($nodeInfo->getScope(), 1);
    }

    public function testGetChildrenNodeInfo()
    {
        $nodeInfo = $this->adapter
            ->getChildrenNodeInfo(2);

        $this->assertCount(3, $nodeInfo);

        // check first node info
        $this->assertEquals($nodeInfo[0]->getId(), 3);
        $this->assertEquals($nodeInfo[0]->getParentId(), 2);
        $this->assertEquals($nodeInfo[0]->getLeft(), 3);
        $this->assertEquals($nodeInfo[0]->getRight(), 4);
        $this->assertEquals($nodeInfo[0]->getLevel(), 2);

        // check last node info
        $this->assertEquals($nodeInfo[2]->getId(), 5);
        $this->assertEquals($nodeInfo[2]->getParentId(), 2);
        $this->assertEquals($nodeInfo[2]->getLeft(), 7);
        $this->assertEquals($nodeInfo[2]->getRight(), 8);
        $this->assertEquals($nodeInfo[2]->getLevel(), 2);
    }

    public function testUpdateNodeMetadata()
    {
        $nodeInfo = new NodeInfo(3, 1000, 1001, 1002, 1003, 2);

        $this->adapter
            ->updateNodeMetadata($nodeInfo);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testUpdateNodeMetadata.xml');
    }

    public function testGetPath()
    {
        $path = $this->adapter
            ->getAncestors(5);

        $this->assertCount(3, $path);

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetPath.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetDescendants()
    {
        $nodes = $this->adapter
            ->getDescendants(1);

        $this->assertCount(5, $nodes);

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetDescendants.php';
        $this->assertEquals($expected, $nodes);
    }
}
