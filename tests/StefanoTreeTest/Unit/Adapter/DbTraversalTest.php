<?php
namespace StefanoTreeTest\Unit\Adapter;

use StefanoTree\Adapter\DbTraversal as TreeAdapter;

class DbTraversalTest
    extends \PHPUnit_Framework_TestCase
{    
    public function testSetGetTableName() {
        $treeAdapter = \Mockery::mock('StefanoTree\Adapter\DbTraversal');
        $treeAdapter->makePartial();
        
        $return = $treeAdapter->setTableName('test');
        $this->assertEquals('test', $treeAdapter->getTableName());
        $this->assertInstanceOf('StefanoTree\Adapter\DbTraversal', $return, 'Must implement fluent interface');
        
        //test
        $this->setExpectedException('\Exception', 'tableName cannot be empty');
        $treeAdapter->setTableName(null);        
    }
    
    public function testSetGetIdColumnName() {
        $treeAdapter = \Mockery::mock('StefanoTree\Adapter\DbTraversal');
        $treeAdapter->makePartial();
        
        $return = $treeAdapter->setIdColumnName('test');
        $this->assertEquals('test', $treeAdapter->getIdColumnName());
        $this->assertInstanceOf('StefanoTree\Adapter\DbTraversal', $return, 'Must implement fluent interface');
        
        //test
        $this->setExpectedException('\Exception', 'idColumnName cannot be empty');
        $treeAdapter->setIdColumnName(null); 
    }
    
    
    public function testSetGetLeftColumnName() {
        $treeAdapter = \Mockery::mock('StefanoTree\Adapter\DbTraversal');
        $treeAdapter->makePartial();
        
        $return = $treeAdapter->setLeftColumnName('test');
        $this->assertEquals('test', $treeAdapter->getLeftColumnName());        
        $this->assertInstanceOf('StefanoTree\Adapter\DbTraversal', $return, 'Must implement fluent interface');
        
        //test
        $this->setExpectedException('\Exception', 'leftColumnName cannot be empty');
        $treeAdapter->setLeftColumnName(null); 
    }
    
    public function testSetGetRightColumnName() {
        $treeAdapter = \Mockery::mock('StefanoTree\Adapter\DbTraversal');
        $treeAdapter->makePartial();
        
        $return = $treeAdapter->setRightColumnName('test');
        $this->assertEquals('test', $treeAdapter->getRightColumnName());
        $this->assertInstanceOf('StefanoTree\Adapter\DbTraversal', $return, 'Must implement fluent interface');
        
        //test
        $this->setExpectedException('\Exception', 'rightColumnName cannot be empty');
        $treeAdapter->setRightColumnName(null); 
    }
    
    public function testSetGetLevelColumnName() {
        $treeAdapter = \Mockery::mock('StefanoTree\Adapter\DbTraversal');
        $treeAdapter->makePartial();
        
        $return = $treeAdapter->setLevelColumnName('test');
        $this->assertEquals('test', $treeAdapter->getLevelColumnName());
        $this->assertInstanceOf('StefanoTree\Adapter\DbTraversal', $return, 'Must implement fluent interface');
        
        //test
        $this->setExpectedException('\Exception', 'levelColumnName cannot be empty');
        $treeAdapter->setLevelColumnName(null);
        
    }
    
    public function testSetGetParentIdColumnName() {
        $treeAdapter = \Mockery::mock('StefanoTree\Adapter\DbTraversal');
        $treeAdapter->makePartial();
        
        $return = $treeAdapter->setParentIdColumnName('test');
        $this->assertEquals('test', $treeAdapter->getParentIdColumnName());
        $this->assertInstanceOf('StefanoTree\Adapter\DbTraversal', $return, 'Must implement fluent interface');
        
        //test
        $this->setExpectedException('\Exception', 'parentIdColumnName cannot be empty');
        $treeAdapter->setParentIdColumnName(null);
    }
    
    public function testSetGetDbAdapter() {
        $treeAdapter = \Mockery::mock('StefanoTree\Adapter\DbTraversal');
        $treeAdapter->makePartial();
        
        $dbAdapterdStub = \Mockery::mock('\StefanoDb\Adapter\Adapter');
        
        $return = $treeAdapter->setDbAdapter($dbAdapterdStub);
        $this->assertSame($dbAdapterdStub, $treeAdapter->getDbAdapter());
        $this->assertInstanceOf('StefanoTree\Adapter\DbTraversal', $return, 'Must implement fluent interface');
    }
    
    public function testObjectConstructorValidation() {
        $this->setExpectedException('\Exception', 'tableName, idColumnName, dbAdapter must be set');
        
        new TreeAdapter(array());
    }    
}