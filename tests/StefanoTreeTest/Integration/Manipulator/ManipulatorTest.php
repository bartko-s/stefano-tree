<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Manipulator;

use StefanoTree\NestedSet\Manipulator\Manipulator;
use StefanoTree\NestedSet\Manipulator\ManipulatorInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\DbTester\ArrayDataSource;
use StefanoTreeTest\IntegrationTestCase;
use StefanoTreeTest\TestUtil;

/**
 * @internal
 */
class ManipulatorTest extends IntegrationTestCase
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
                'tableName' => 'tree_traversal',
                'idColumnName' => 'tree_traversal_id',
            ));

            $this->manipulator = new Manipulator($options, TestUtil::buildAdapter($options));
        }

        return $this->manipulator;
    }

    protected function getDataSet(): ArrayDataSource
    {
        return $this->createArrayDataSet(include __DIR__.'/_files/adapter/initDataSet.php');
    }

    public function testLockTreeDoesNotFail(): void
    {
        $this->getManipulator()
            ->lockTree();
    }

    public function testDbTransactionDoesNotFail(): void
    {
        $this->getManipulator()
            ->beginTransaction();
        $this->getManipulator()
            ->commitTransaction();

        $this->getManipulator()
            ->beginTransaction();
        $this->getManipulator()
            ->rollbackTransaction();
    }

    public function testUpdateData(): void
    {
        $this->getManipulator()
            ->update(2, array('name' => 'changed'));

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateData.php');
    }

    public function testUpdateDataDoesNotChangeMetadata(): void
    {
        $data = array(
            'name' => 'changed',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'tree_traversal_id' => 1596,
            'level' => 'd',
        );

        $this->getManipulator()
            ->update(2, $data);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateData.php');
    }

    public function testInsertData(): void
    {
        $nodeInfo = new NodeInfo(null, 6, 100, 1000, 1001, null);

        $generatedId = $this->getManipulator()
            ->insert($nodeInfo, array('name' => 'some-name'));

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testInsertData.php');
        $this->assertEquals(26, $generatedId);
    }

    public function testInsertDataUserDefinedId(): void
    {
        $uuid = 753;
        $nodeInfo = new NodeInfo(null, 6, 100, 1000, 1001, null);

        $generatedId = $this->getManipulator()
            ->insert($nodeInfo, array('name' => 'some-name', 'tree_traversal_id' => $uuid));

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testInsertDataUserDefinedId.php');
        $this->assertEquals($uuid, $generatedId);
    }

    public function testInsertDataDoesNotChangeMetadata(): void
    {
        $nodeInfo = new NodeInfo(null, 6, 100, 1000, 1001, null);

        $data = array(
            'name' => 'some-name',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'level' => 'd',
        );

        $generatedId = $this->getManipulator()
            ->insert($nodeInfo, $data);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testInsertData.php');
        $this->assertEquals(26, $generatedId);
    }

    public function testDeleteBranch(): void
    {
        $this->getManipulator()
            ->delete(3);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testDeleteBranch.php');
    }

    public function testMoveLeftIndexes(): void
    {
        $this->getManipulator()
            ->moveLeftIndexes(12, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testMoveLeftIndexes.php');
    }

    public function testMoveRightIndexes(): void
    {
        $this->getManipulator()
            ->moveRightIndexes(15, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testMoveRightIndexes.php');
    }

    public function testUpdateParentId(): void
    {
        $this->getManipulator()
            ->updateParentId(3, 22);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateParentId.php');
    }

    public function testUpdateLevels(): void
    {
        $this->getManipulator()
            ->updateLevels(16, 35, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateLevels.php');
    }

    public function testMoveBranch(): void
    {
        $this->getManipulator()
            ->moveBranch(17, 32, 500);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testMoveBranch.php');
    }

    public function testGetRoots(): void
    {
        $roots = $this->getManipulator()
            ->getRoots();

        $expected = include __DIR__.'/_files/adapter/testGetRoots.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetRoot(): void
    {
        $roots = $this->getManipulator()
            ->getRoot();

        $expected = include __DIR__.'/_files/adapter/testGetRoot.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetNodeReturnNullIfNodeDoesNotExist(): void
    {
        $node = $this->getManipulator()
            ->getNode(1000000);
        $this->assertNull($node);
    }

    public function testGetNode(): void
    {
        $node = $this->getManipulator()
            ->getNode(11);

        $expected = include __DIR__.'/_files/adapter/testGetNode.php';
        $this->assertEquals($expected, $node);
    }

    public function testGetNodeInfoReturnNullIfNodeInfoDoesNotExist(): void
    {
        $nodeInfo = $this->getManipulator()
            ->getNodeInfo(10000000);
        $this->assertNull($nodeInfo);
    }

    public function testGetNodeInfo(): void
    {
        $nodeInfo = $this->getManipulator()
            ->getNodeInfo(10);

        $this->assertNotNull($nodeInfo);
        $this->assertEquals($nodeInfo->getId(), 10);
        $this->assertEquals($nodeInfo->getParentId(), 5);
        $this->assertEquals($nodeInfo->getLeft(), 4);
        $this->assertEquals($nodeInfo->getRight(), 11);
        $this->assertEquals($nodeInfo->getLevel(), 3);
    }

    public function testGetChildrenNodeInfoReturnEmptyArrayIfNodeDoesNotHaveChildrenNodes(): void
    {
        $nodeInfo = $this->getManipulator()
            ->getChildrenNodeInfo(7);

        $this->assertEquals(array(), $nodeInfo);
    }

    public function testGetChildrenNodeInfo(): void
    {
        $nodeInfo = $this->getManipulator()
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

    public function testUpdateNodeMetadata(): void
    {
        $nodeInfo = new NodeInfo(2, 100, 101, 102, 103, null);

        $this->getManipulator()
            ->updateNodeMetadata($nodeInfo);

        $this->assertCompareDataSet(array('tree_traversal'), __DIR__.'/_files/adapter/testUpdateNodeMetadata.php');
    }

    public function testGetAncestorsReturnEmptyArrayIfNodeDoestNotExist(): void
    {
        $path = $this->getManipulator()
            ->getAncestors(1000);

        $this->assertEquals(array(), $path);
    }

    public function testGetAncestors(): void
    {
        $path = $this->getManipulator()
            ->getAncestors(10);

        $this->assertCount(4, $path);

        $expected = include __DIR__.'/_files/adapter/testGetAncestors.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetAncestorsFromLevel(): void
    {
        $path = $this->getManipulator()
            ->getAncestors(10, 2);

        $this->assertCount(2, $path);

        $expected = include __DIR__.'/_files/adapter/testGetAncestorsStartFromLevel.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetAncestorsExcludeLastNode(): void
    {
        // test exclude last node
        $path = $this->getManipulator()
            ->getAncestors(10, 0, 1);

        $this->assertCount(3, $path);
        $expected = include __DIR__.'/_files/adapter/testGetAncestorsExcludeLastNode.php';
        $this->assertEquals($expected, $path);

        // test exclude last two node
        $path = $this->getManipulator()
            ->getAncestors(10, 0, 2);

        $this->assertCount(2, $path);
        $expected = include __DIR__.'/_files/adapter/testGetAncestorsExcludeTwoLastNode.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetDescendantsReturnEmptyArrayIfNodeDoesNotExist(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(1000);

        $this->assertEquals(array(), $nodes);
    }

    public function testGetDescendants(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(1);

        $this->assertCount(25, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendants.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsDefinedNodeId(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(6);

        $this->assertCount(8, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsDefinedNodeId.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsFromLevel(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(6, 2);

        $this->assertCount(5, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsFromLevel.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsFixLevels(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(6, 2, 2);

        $this->assertCount(3, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsFixLevels.php';
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendantsExcludeBranch(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(1, 0, null, 9);

        $this->assertCount(20, $nodes);

        $expected = include __DIR__.'/_files/adapter/testGetDescendantsExcludeBranch.php';
        $this->assertEquals($expected, $nodes);
    }
}
