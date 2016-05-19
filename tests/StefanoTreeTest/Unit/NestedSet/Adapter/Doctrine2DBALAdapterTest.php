<?php
namespace StefanoTreeTest\Unit\NestedSet\Adapter;

use StefanoTree\NestedSet\Adapter\Doctrine2DBALAdapter;
use StefanoTree\NestedSet\Options;

class Doctrine2DBALAdapterTest
    extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        \Mockery::close();
    }

    public function testGetDefaultDbSelect()
    {
        $adapter = $this->getAdapter();

        $this->assertEquals('SELECT * FROM tableName',
            trim($adapter->getDefaultDbSelect()->getSQL()));
    }

    public function testGetDefaultDbSelectMustAlwaysReturnNewInstance()
    {
        $adapter = $this->getAdapter();
        $this->assertNotSame($adapter->getDefaultDbSelect(), $adapter->getDefaultDbSelect());
    }

    public function testSetDefaultDbSelect()
    {
        $adapter = $this->getAdapter();

        $select = $this->getConnection()
                       ->createQueryBuilder()
                       ->select('*')
                       ->from('someTable', null);

        $adapter->setDefaultDbSelect($select);

        $this->assertEquals($select->getSQL(), $adapter->getDefaultDbSelect()->getSQL());
    }

    /**
     * @return Doctrine2DBALAdapter
     */
    private function getAdapter()
    {
        $options = new Options(array(
            'tableName'    => 'tableName',
            'idColumnName' => 'id',
        ));

        return new Doctrine2DBALAdapter($options, $this->getConnection());
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname' => ':memory:',
            'driver' => 'pdo_sqlite',
        );

        return  \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }
}
