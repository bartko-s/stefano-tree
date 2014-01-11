<?php
namespace StefanoTreeTest\Unit\NestedSet;

use StefanoTree\NestedSet\NodeInfo;

class NodeInfoTest
    extends \PHPUnit_Framework_TestCase
{
    public function testNodeInfo() {        
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
    }
    
    public function testThrowExceptionIfObjectIsNotFullyInitialized() {
        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException', 
                'Params "id, parentId, level, left, right" must be set');
        
        new NodeInfo(array());
    }
}