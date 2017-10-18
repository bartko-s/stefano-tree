<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit\NestedSet\Adapter;

use Mockery;
use StefanoTree\NestedSet\Adapter\Zend2 as NestedSetAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\UnitTestCase;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Sql\Select as SqlSelect;

class Zend2Test extends UnitTestCase
{
    protected function tearDown()
    {
        Mockery::close();
    }

    public function testGetBlankDbSelect()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new NestedSetAdapter($options, $dbAdapter);

        $this->assertEquals('SELECT "tableName".* FROM "tableName" AS "tableName"',
            $adapter->getBlankDbSelect()->getSqlString());
    }

    public function testGetDefaultDbSelectMustAlwaysReturnNewInstance()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new NestedSetAdapter($options, $dbAdapter);

        $this->assertNotSame($adapter->getDefaultDbSelect(), $adapter->getDefaultDbSelect());
    }

    public function testSetDefaultDbSelect()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new NestedSetAdapter($options, $dbAdapter);

        $select = new SqlSelect('table');

        $adapter->setDefaultDbSelect($select);

        $this->assertEquals($select->getSqlString(), $adapter->getDefaultDbSelect()->getSqlString());
    }

    /**
     * @return DbAdapter
     */
    private function getDbAdapterMock()
    {
        $dbA = new DbAdapter(array(
            'driver' => 'Pdo_Sqlite',
            'database' => ':memory:',
        ));

        $dbAdapterMock = Mockery::mock($dbA);
        $dbAdapterMock->makePartial();

        return $dbAdapterMock;
    }
}
