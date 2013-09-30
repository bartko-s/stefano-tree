<?php
namespace StefanoTreeTest\Unit;

use \StefanoTree\NodeInfo;

class NodeInfoTest
    extends \PHPUnit_Framework_TestCase
{
    public function testNodeInfo() {
        //test
        $param = array(
            'id'        => 11,
            'parentId'  => 29,
            'level'     => 33,
            'left'      => 44,
            'right'     => 62,
        );
        
        $nodeInfo = new NodeInfo($param);
        
        $this->assertEquals(11, $nodeInfo->getId());
        $this->assertEquals(29, $nodeInfo->getParentId());
        $this->assertEquals(33, $nodeInfo->getLevel());
        $this->assertEquals(44, $nodeInfo->getLeft());
        $this->assertEquals(62, $nodeInfo->getRight());
        
        //test
        $this->setExpectedException('\Exception');
        $nodeInfo2 = new NodeInfo(array());
    }
}