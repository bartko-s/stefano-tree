<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit;

use Doctrine\DBAL\Connection;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\DataProvider;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\NestedSet;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\UnitTestCase;

/**
 * @internal
 */
class NestedSetTest extends UnitTestCase
{
    private $options = array(
        'idColumnName' => 'id',
        'tableName' => 'table',
    );

    /**
     * @param mixed $dbAdapterClass
     * @param mixed $expectedAdapterClass
     */
    #[DataProvider('dataProvider')]
    public function testConstructorMethodWithOptionAsObject($dbAdapterClass, $expectedAdapterClass)
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = new Options($this->options);

        $tree = new NestedSet($options, $dbAdapterStub);
        $adapter = $tree->getManipulator()->getAdapter()->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    /**
     * @param mixed $dbAdapterClass
     * @param mixed $expectedAdapterClass
     */
    #[DataProvider('dataProvider')]
    public function testConstructorMethodWithOptionAsArray($dbAdapterClass, $expectedAdapterClass)
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = $this->options;

        $tree = new NestedSet($options, $dbAdapterStub);
        $adapter = $tree->getManipulator()->getAdapter()->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    public static function dataProvider()
    {
        return array(
            array(
                \PDO::class,
                NestedSet\Adapter\Pdo::class,
            ),
            array(
                Adapter::class,
                NestedSet\Adapter\LaminasDb::class,
            ),
            array(
                Connection::class,
                NestedSet\Adapter\DoctrineDBAL::class,
            ),
        );
    }

    public function testThrowExceptionIfYourDbAdapterIsNotSupporter()
    {
        $options = new Options($this->options);
        $dbAdapter = new \DateTime();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Db adapter "DateTime" is not supported');

        new NestedSet($options, $dbAdapter);
    }
}
