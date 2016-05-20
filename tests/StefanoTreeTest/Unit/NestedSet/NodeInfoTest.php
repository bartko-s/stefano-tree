<?php
namespace StefanoTreeTest\Unit\NestedSet;

use StefanoTree\NestedSet\NodeInfo;

class NodeInfoTest
    extends \PHPUnit_Framework_TestCase
{
    public function testNodeInfo()
    {
        $nodeInfo = new NodeInfo(11, 29, 33, 44, 62);

        $this->assertEquals(11, $nodeInfo->getId());
        $this->assertEquals(29, $nodeInfo->getParentId());
        $this->assertEquals(33, $nodeInfo->getLevel());
        $this->assertEquals(44, $nodeInfo->getLeft());
        $this->assertEquals(62, $nodeInfo->getRight());
    }

    public function testIsNotRoot()
    {
        $nodeInfo = new NodeInfo(11, 29, 33, 44, 62);

        $this->assertFalse($nodeInfo->isRoot());
    }

    public function testIsRoot()
    {
        $nodeInfo = new NodeInfo(125, 0, 0, 1, 156);

        $this->assertTrue($nodeInfo->isRoot());
    }
}
