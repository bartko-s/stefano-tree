<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit;

use StefanoTree\NestedSet;
use StefanoTreeTest\UnitTestCase;

class NestedSetTest extends UnitTestCase
{
    private $options = array(
        'idColumnName' => 'id',
        'tableName' => 'table',
    );

    public function factoryDataProvider()
    {
        return array(
            array(
                \Zend\Db\Adapter\Adapter::class,
                \StefanoTree\NestedSet\Adapter\Zend2::class,
            ),
            array(
                \Doctrine\DBAL\Connection::class,
                \StefanoTree\NestedSet\Adapter\Doctrine2DBAL::class,
            ),
            array(
                \Zend_Db_Adapter_Abstract::class,
                \StefanoTree\NestedSet\Adapter\Zend1::class,
            ),
        );
    }

    /**
     * @dataProvider factoryDataProvider
     */
    public function testFactoryMethodWithOptionAsObject($dbAdapterClass, $expectedAdapterClass)
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = new \StefanoTree\NestedSet\Options($this->options);

        $tree = NestedSet::factory($options, $dbAdapterStub);
        $adapter = $tree->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    /**
     * @dataProvider factoryDataProvider
     */
    public function testFactoryMethodWithOptionAsArray($dbAdapterClass, $expectedAdapterClass)
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = $this->options;

        $tree = NestedSet::factory($options, $dbAdapterStub);
        $adapter = $tree->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    public function testThrowExceptionIfYourDbAdapterIsNotSupporter()
    {
        $options = new \StefanoTree\NestedSet\Options($this->options);
        $dbAdapter = new \DateTime();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Db adapter "DateTime" is not supported');

        NestedSet::factory($options, $dbAdapter);
    }
}
