<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit\NestedSet;

use StefanoTree\NestedSet\NodeInfo;
use StefanoTreeTest\UnitTestCase;

/**
 * @internal
 * @coversNothing
 */
class NodeInfoTest extends UnitTestCase
{
    public function testNodeInfoWithScope()
    {
        $nodeInfo = new NodeInfo(11, 29, 33, 44, 62, 45);

        $this->assertEquals(11, $nodeInfo->getId());
        $this->assertEquals(29, $nodeInfo->getParentId());
        $this->assertEquals(33, $nodeInfo->getLevel());
        $this->assertEquals(44, $nodeInfo->getLeft());
        $this->assertEquals(62, $nodeInfo->getRight());
        $this->assertEquals(45, $nodeInfo->getScope());
    }

    public function testSetLevel()
    {
        $nodeInfo = new NodeInfo(0, 0, 0, 0, 0, 0);

        $nodeInfo->setLevel(123);
        $this->assertEquals(123, $nodeInfo->getLevel());
    }

    public function testSetLeftIndex()
    {
        $nodeInfo = new NodeInfo(0, 0, 0, 0, 0, 0);

        $nodeInfo->setLeft(456);
        $this->assertEquals(456, $nodeInfo->getLeft());
    }

    public function testSetRightIndex()
    {
        $nodeInfo = new NodeInfo(0, 0, 0, 0, 0, 0);

        $nodeInfo->setRight(789);
        $this->assertEquals(789, $nodeInfo->getRight());
    }

    public function testIsNotRoot()
    {
        $nodeInfo = new NodeInfo(11, 29, 33, 44, 62, null);
        $this->assertFalse($nodeInfo->isRoot());

        $nodeInfo = new NodeInfo('acs', 'cst', 33, 44, 62, null);
        $this->assertFalse($nodeInfo->isRoot());
    }

    public function testIsRoot()
    {
        $nodeInfo = new NodeInfo(25, null, 0, 1, 186, null);
        $this->assertTrue($nodeInfo->isRoot());

        $nodeInfo = new NodeInfo('ac', null, 0, 1, 16, 'b');
        $this->assertTrue($nodeInfo->isRoot());
    }
}
