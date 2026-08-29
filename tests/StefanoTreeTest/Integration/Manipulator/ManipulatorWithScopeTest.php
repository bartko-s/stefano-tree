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
class ManipulatorWithScopeTest extends IntegrationTestCase
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
                'tableName' => 'tree_traversal_with_scope',
                'idColumnName' => 'tree_traversal_id',
                'scopeColumnName' => 'scope',
            ));

            $this->manipulator = new Manipulator($options, TestUtil::buildAdapter($options));
        }

        return $this->manipulator;
    }

    protected function getDataSet(): ArrayDataSource
    {
        return $this->createArrayDataSet(include __DIR__.'/_files/adapter/with_scope/initDataSet.php');
    }

    public function testUpdateDataDoesNotChangeMetadata(): void
    {
        $data = array(
            'name' => 'changed',
            'lft' => 'a',
            'rgt' => 'b',
            'parent_id' => 'c',
            'level' => 'd',
            'scope' => 'e',
        );

        $this->getManipulator()
            ->update(2, $data);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testUpdateData.php');
    }

    public function testInsertDataDoesNotChangeMetadata(): void
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

        $this->getManipulator()
            ->insert($nodeInfo, $data);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testInsertData.php');
    }

    public function testDeleteBranch(): void
    {
        $this->getManipulator()
            ->delete(2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testDeleteBranch.php');
    }

    public function testMoveLeftIndexes(): void
    {
        $this->getManipulator()
            ->moveLeftIndexes(3, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testMoveLeftIndexes.php');
    }

    public function testMoveRightIndexes(): void
    {
        $this->getManipulator()
            ->moveRightIndexes(4, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testMoveRightIndexes.php');
    }

    public function testUpdateLevels(): void
    {
        $this->getManipulator()
            ->updateLevels(2, 9, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testUpdateLevels.php');
    }

    public function testMoveBranch(): void
    {
        $this->getManipulator()
            ->moveBranch(2, 9, 500, 2);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testMoveBranch.php');
    }

    public function testGetRoots(): void
    {
        $roots = $this->getManipulator()
            ->getRoots();

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetRoots.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetRoot(): void
    {
        $roots = $this->getManipulator()
            ->getRoot(2);

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetRoot.php';
        $this->assertEquals($expected, $roots);
    }

    public function testGetNodeInfo(): void
    {
        $nodeInfo = $this->getManipulator()
            ->getNodeInfo(8);

        $this->assertNotNull($nodeInfo);
        $this->assertEquals($nodeInfo->getId(), 8);
        $this->assertEquals($nodeInfo->getParentId(), 7);
        $this->assertEquals($nodeInfo->getLeft(), 3);
        $this->assertEquals($nodeInfo->getRight(), 8);
        $this->assertEquals($nodeInfo->getLevel(), 2);
        $this->assertEquals($nodeInfo->getScope(), 1);
    }

    public function testGetChildrenNodeInfo(): void
    {
        $nodeInfo = $this->getManipulator()
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

    public function testUpdateNodeMetadata(): void
    {
        $nodeInfo = new NodeInfo(3, 1000, 1001, 1002, 1003, 2);

        $this->getManipulator()
            ->updateNodeMetadata($nodeInfo);

        $this->assertCompareDataSet(array('tree_traversal_with_scope'), __DIR__.'/_files/adapter/with_scope/testUpdateNodeMetadata.php');
    }

    public function testGetPath(): void
    {
        $path = $this->getManipulator()
            ->getAncestors(5);

        $this->assertCount(3, $path);

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetPath.php';
        $this->assertEquals($expected, $path);
    }

    public function testGetDescendants(): void
    {
        $nodes = $this->getManipulator()
            ->getDescendants(1);

        $this->assertCount(5, $nodes);

        $expected = include __DIR__.'/_files/adapter/with_scope/testGetDescendants.php';
        $this->assertEquals($expected, $nodes);
    }
}
