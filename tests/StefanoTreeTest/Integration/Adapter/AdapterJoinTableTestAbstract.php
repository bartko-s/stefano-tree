<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTreeTest\IntegrationTestCase;

abstract class AdapterJoinTableTestAbstract extends IntegrationTestCase
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
        return $this->createMySQLXMLDataSet(__DIR__.'/_files/adapter/join_table/initDataSet.xml');
    }

    public function testGetNode()
    {
        $nodes = $this->adapter
            ->getDescendants(10);

        $expected = array(
            array(
                'tree_traversal_id' => 10,
                'name' => null,
                'lft' => 5,
                'rgt' => 6,
                'parent_id' => 9,
                'level' => 4,
                'scope' => 1,
                'metadata' => 'meta-10',
            ),
        );
        $this->assertEquals($expected, $nodes);
    }

    public function testGetAncestors()
    {
        $nodes = $this->adapter
            ->getAncestors(10, 2, 1);

        $expected = array(
            array(
                'tree_traversal_id' => 8,
                'name' => null,
                'lft' => 3,
                'rgt' => 8,
                'parent_id' => 7,
                'level' => 2,
                'scope' => 1,
                'metadata' => null,
            ),
            array(
                'tree_traversal_id' => 9,
                'name' => null,
                'lft' => 4,
                'rgt' => 7,
                'parent_id' => 8,
                'level' => 3,
                'scope' => 1,
                'metadata' => 'meta-9',
            ),
        );
        $this->assertEquals($expected, $nodes);
    }

    public function testGetDescendants()
    {
        $nodes = $this->adapter
            ->getDescendants(2, 1, 1, 4);

        $expected = array(
            array(
                'tree_traversal_id' => 3,
                'name' => null,
                'lft' => 3,
                'rgt' => 4,
                'parent_id' => 2,
                'level' => 2,
                'scope' => 2,
                'metadata' => null,
            ),
            array(
                'tree_traversal_id' => 5,
                'name' => null,
                'lft' => 7,
                'rgt' => 8,
                'parent_id' => 2,
                'level' => 2,
                'scope' => 2,
                'metadata' => null,
            ),
        );
        $this->assertEquals($expected, $nodes);
    }

    public function testGetChildrenNodeInfo()
    {
        $nodes = $this->adapter
            ->getChildrenNodeInfo(2);

        $this->assertEquals(3, count($nodes));
    }

    public function testGetNodeInfo()
    {
        $nodeInfo = $this->adapter
            ->getNodeInfo(2);

        $this->assertEquals($nodeInfo->getId(), 2);
        $this->assertEquals($nodeInfo->getParentId(), 1);
        $this->assertEquals($nodeInfo->getLeft(), 2);
        $this->assertEquals($nodeInfo->getRight(), 9);
        $this->assertEquals($nodeInfo->getLevel(), 1);
        $this->assertEquals($nodeInfo->getScope(), 2);
    }
}
