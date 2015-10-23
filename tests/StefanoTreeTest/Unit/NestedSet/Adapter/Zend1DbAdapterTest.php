<?php
namespace StefanoTreeTest\Unit\NestedSet\Adapter;

use StefanoTree\NestedSet\Adapter\Zend1DbAdapter;
use StefanoTree\NestedSet\Options;

class Zend1DbAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        \Mockery::close();
    }

    public function testGetDefaultDbSelect()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend1DbAdapter($options, $dbAdapter);

        $expectedQuery = 'SELECT "tableName".* FROM "tableName"';
        $actualQuery = (string) $adapter->getDefaultDbSelect();
        $this->assertEquals($expectedQuery,
            $actualQuery);
    }

    public function testGetDefaultDbSelectMustAlwaysReturnNewInstance()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend1DbAdapter($options, $dbAdapter);

        $this->assertNotSame($adapter->getDefaultDbSelect(), $adapter->getDefaultDbSelect());
    }

    public function testSetDefaulDbSelect()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend1DbAdapter($options, $dbAdapter);

        $select = $dbAdapter->select()->from('tableName');

        $adapter->setDefaultDbSelect($select);

        $this->assertEquals($select->__toString(), $adapter->getDefaultDbSelect()->__toString());
    }

    /**
     * @return \Zend_Db_Adapter_Pdo_Sqlite
     */
    protected function getDbAdapterMock()
    {
        $dbA = \Zend_Db::factory('Pdo_Sqlite', array(
            'database' => ':memory:',
            'dbname' => TEST_STEFANO_DB_DB_NAME,
        ));

        $dbAdapterMock = \Mockery::mock($dbA);
        $dbAdapterMock->makePartial();

        return $dbAdapterMock;
    }
}
