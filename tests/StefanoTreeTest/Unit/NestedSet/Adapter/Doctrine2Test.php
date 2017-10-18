<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit\NestedSet\Adapter;

use Doctrine\DBAL;
use StefanoTree\NestedSet\Adapter\Doctrine2DBAL;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\UnitTestCase;

class Doctrine2DBALAdapterTest extends UnitTestCase
{
    protected function tearDown()
    {
        \Mockery::close();
    }

    public function testGetBlankDbSelect()
    {
        $adapter = $this->getAdapter();

        $this->assertEquals('SELECT tableName.* FROM tableName tableName',
            trim($adapter->getBlankDbSelect()->getSQL()));
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
     * @return Doctrine2DBAL
     */
    private function getAdapter()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        return new Doctrine2DBAL($options, $this->getConnection());
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        $config = new DBAL\Configuration();

        $connectionParams = array(
            'dbname' => ':memory:',
            'driver' => 'pdo_sqlite',
        );

        return  DBAL\DriverManager::getConnection($connectionParams, $config);
    }
}
