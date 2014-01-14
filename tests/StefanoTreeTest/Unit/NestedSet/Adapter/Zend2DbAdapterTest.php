<?php
namespace StefanoTreeTest\Unit\NestedSet\Adapter;

use StefanoTree\NestedSet\Adapter\Zend2DbAdapter;
use StefanoTree\NestedSet\Options;

class Zend2DbAdapterTest
    extends \PHPUnit_Framework_TestCase
{
    protected function tearDown() {
        \Mockery::close();
    }

    public function testGetDefaultDbSelect() {
        $options = new Options(array(
            'tableName'    => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend2DbAdapter($options, $dbAdapter);

        $this->assertEquals('SELECT "tableName".* FROM "tableName"',
            $adapter->getDefaultDbSelect()->getSqlString());
    }

    public function testGetDefaultDbSelectMustAlwaysReturnNewInstance() {
        $options = new Options(array(
            'tableName'    => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend2DbAdapter($options, $dbAdapter);

        $this->assertNotSame($adapter->getDefaultDbSelect(), $adapter->getDefaultDbSelect());
    }

    public function testSetDefaulDbSelect() {
        $options = new Options(array(
            'tableName'    => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend2DbAdapter($options, $dbAdapter);

        $select = new \Zend\Db\Sql\Select('table');

        $adapter->setDefaultDbSelect($select);

        $this->assertEquals($select->getSqlString()
            , $adapter->getDefaultDbSelect()->getSqlString());
    }

    /**
    * @return \StefanoDb\Adapter\Adapter
    */
    private function getDbAdapterMock() {
        $dbA = new \StefanoDb\Adapter\Adapter(array(
            'driver' => 'Pdo_Sqlite',
            'database' => ':memory:',
        ));

        $dbAdapterMock = \Mockery::mock($dbA);
        $dbAdapterMock->makePartial();

        return $dbAdapterMock;
    }
}