<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit;

use Doctrine\DBAL\Connection;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\DataProvider;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\NestedSet;
use StefanoTree\NestedSet\Adapter\NestedTransactionDecorator;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\UnitTestCase;

/**
 * @internal
 */
class NestedSetTest extends UnitTestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $options = array(
        'idColumnName' => 'id',
        'tableName' => 'table',
    );

    #[DataProvider('dataProvider')]
    public function testConstructorMethodWithOptionAsObject(string $dbAdapterClass, string $expectedAdapterClass): void
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = new Options($this->options);

        $tree = new NestedSet($options, $dbAdapterStub);
        $adapterDecorator = $tree->getManipulator()->getAdapter();
        \assert($adapterDecorator instanceof NestedTransactionDecorator);
        $adapter = $adapterDecorator->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    #[DataProvider('dataProvider')]
    public function testConstructorMethodWithOptionAsArray(string $dbAdapterClass, string $expectedAdapterClass): void
    {
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);
        $options = $this->options;

        $tree = new NestedSet($options, $dbAdapterStub);
        $adapterDecorator = $tree->getManipulator()->getAdapter();
        \assert($adapterDecorator instanceof NestedTransactionDecorator);
        $adapter = $adapterDecorator->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function dataProvider(): array
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

    public function testThrowExceptionIfYourDbAdapterIsNotSupporter(): void
    {
        $options = new Options($this->options);
        $dbAdapter = new \DateTime();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Db adapter "DateTime" is not supported');

        new NestedSet($options, $dbAdapter);
    }
}
