<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit\NestedSet\Adapter;

use StefanoTree\NestedSet\Adapter\Zend1;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\UnitTestCase;

class Zend1Test extends UnitTestCase
{
    protected function tearDown()
    {
        \Mockery::close();
    }

    public function testGetBlankDbSelect()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend1($options, $dbAdapter);

        $expectedQuery = 'SELECT "tableName".* FROM "tableName"';
        $actualQuery = (string) $adapter->getBlankDbSelect();
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
        $adapter = new Zend1($options, $dbAdapter);

        $this->assertNotSame($adapter->getDefaultDbSelect(), $adapter->getDefaultDbSelect());
    }

    public function testSetDefaultDbSelectBuilder()
    {
        $options = new Options(array(
            'tableName' => 'tableName',
            'idColumnName' => 'id',
        ));

        $dbAdapter = $this->getDbAdapterMock();
        $adapter = new Zend1($options, $dbAdapter);

        $builder = function () use ($dbAdapter) {
            return $dbAdapter->select()->from('tableName');
        };

        $adapter->setDbSelectBuilder($builder);

        $this->assertEquals($builder()->__toString(), $adapter->getDefaultDbSelect()->__toString());
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
