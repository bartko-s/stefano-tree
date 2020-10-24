<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit;

use StefanoTree\NestedSet;
use StefanoTreeTest\UnitTestCase;

/**
 * @internal
 * @coversNothing
 */
class NestedSetTest extends UnitTestCase
{
    private $options = array(
        'idColumnName' => 'id',
        'tableName' => 'table',
    );

    public function dataProvider()
    {
        return array(
            array(
                \PDO::class,
                NestedSet\Adapter\Pdo::class,
            ),
            array(
                \Zend\Db\Adapter\Adapter::class,
                NestedSet\Adapter\Zend2::class,
            ),
            array(
                \Doctrine\DBAL\Connection::class,
                NestedSet\Adapter\Doctrine2DBAL::class,
            ),
            array(
                \Zend_Db_Adapter_Abstract::class,
                NestedSet\Adapter\Zend1::class,
            ),
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param mixed $dbAdapterClass
     * @param mixed $expectedAdapterClass
     */
    public function testConstructorMethodWithOptionAsObject($dbAdapterClass, $expectedAdapterClass)
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = new \StefanoTree\NestedSet\Options($this->options);

        $tree = new NestedSet($options, $dbAdapterStub);
        $adapter = $tree->getManipulator()->getAdapter()->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param mixed $dbAdapterClass
     * @param mixed $expectedAdapterClass
     */
    public function testConstructorMethodWithOptionAsArray($dbAdapterClass, $expectedAdapterClass)
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = $this->options;

        $tree = new NestedSet($options, $dbAdapterStub);
        $adapter = $tree->getManipulator()->getAdapter()->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    public function testThrowExceptionIfYourDbAdapterIsNotSupporter()
    {
        $options = new \StefanoTree\NestedSet\Options($this->options);
        $dbAdapter = new \DateTime();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Db adapter "DateTime" is not supported');

        new NestedSet($options, $dbAdapter);
    }
}
