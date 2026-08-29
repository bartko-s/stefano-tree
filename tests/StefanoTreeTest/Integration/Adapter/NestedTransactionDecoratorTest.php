<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use Mockery\MockInterface;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Adapter\NestedTransactionDecorator;
use StefanoTree\NestedSet\Adapter\Pdo;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;
use StefanoTreeTest\UnitTestCase;

/**
 * @internal
 */
class NestedTransactionDecoratorTest extends UnitTestCase
{
    protected ?AdapterInterface $adapterNestedDoNotSupport = null;

    protected \PDO $dbAdapter;

    protected function setUp(): void
    {
        $this->dbAdapter = TestUtil::getPDOConnection();

        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));

        $this->adapterNestedDoNotSupport = new Pdo($options, $this->dbAdapter);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->adapterNestedDoNotSupport = null;
        parent::tearDown();
    }

    public function testCanHandleNestedTransaction(): void
    {
        $adapterStub = \Mockery::mock(AdapterInterface::class);

        $adapter = new NestedTransactionDecorator($adapterStub);
        $this->assertTrue($adapter->canHandleNestedTransaction());
    }

    public function testWrappedAdapterCanHandleHandleNestedTransaction(): void
    {
        $adapterMock = \Mockery::mock(AdapterInterface::class);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnTrue();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(3);

        $adapterMock->shouldReceive('commitTransaction')
            ->times(2);

        $adapterMock->shouldReceive('rollbackTransaction')
            ->times(1);

        $adapterMock->shouldReceive('isInTransaction')
            ->andReturnFalse();

        $adapter = new NestedTransactionDecorator($adapterMock);
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->commitTransaction();
        $adapter->commitTransaction();
        $adapter->rollbackTransaction();
    }

    public function testHandleTransaction(): void
    {
        $adapterMock = \Mockery::mock(AdapterInterface::class);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnFalse();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(1);

        $adapterMock->shouldReceive('commitTransaction')
            ->times(1);

        $adapterMock->shouldReceive('isInTransaction')
            ->andReturnFalse();

        $adapter = new NestedTransactionDecorator($adapterMock);
        $adapter->beginTransaction();
        $adapter->commitTransaction();
    }

    public function testHandleBrokenTransaction(): void
    {
        $adapterMock = \Mockery::mock(AdapterInterface::class);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnFalse();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(1);

        $adapterMock->shouldReceive('rollbackTransaction')
            ->times(1);

        $adapterMock->shouldReceive('isInTransaction')
            ->andReturnFalse();

        $adapter = new NestedTransactionDecorator($adapterMock);
        $adapter->beginTransaction();
        $adapter->rollbackTransaction();
    }

    public function testRollbackOnlyMarkIsSetToFalseAfterRollbackSuccess(): void
    {
        $adapterMock = \Mockery::mock(AdapterInterface::class);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnFalse();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(3);

        $adapterMock->shouldReceive('commitTransaction')
            ->times(2);

        $adapterMock->shouldReceive('rollbackTransaction')
            ->times(1);

        $adapterMock->shouldReceive('isInTransaction')
            ->andReturnFalse();

        $adapter = new NestedTransactionDecorator($adapterMock);
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->rollbackTransaction();
        $adapter->rollbackTransaction();

        $adapter->beginTransaction();
        $adapter->commitTransaction();

        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->commitTransaction();
        $adapter->commitTransaction();
    }

    public function testHandleNestedTransaction(): void
    {
        $adapterMock = \Mockery::mock(AdapterInterface::class);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnFalse();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(1);

        $adapterMock->shouldReceive('commitTransaction')
            ->times(1);

        $adapterMock->shouldReceive('isInTransaction')
            ->andReturnFalse();

        $adapter = new NestedTransactionDecorator($adapterMock);
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->commitTransaction();
        $adapter->commitTransaction();
    }

    public function testHandleBrokenNestedTransaction(): void
    {
        $adapterMock = \Mockery::mock(AdapterInterface::class);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnFalse();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(1);

        $adapterMock->shouldReceive('rollbackTransaction')
            ->times(1);

        $adapterMock->shouldReceive('isInTransaction')
            ->andReturnFalse();

        $adapter = new NestedTransactionDecorator($adapterMock);
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->rollbackTransaction();
        $adapter->rollbackTransaction();
    }

    public function testBrokenTransactionIsRollbackOnly(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot commit Transaction was marked as rollback only');

        $adapterMock = \Mockery::mock(AdapterInterface::class);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnFalse();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(1);

        $adapterMock->shouldReceive('commitTransaction')
            ->times(0);

        $adapterMock->shouldReceive('rollbackTransaction');

        $adapterMock->shouldReceive('isInTransaction')
            ->andReturnFalse();

        $adapter = new NestedTransactionDecorator($adapterMock);
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->rollbackTransaction();
        $adapter->commitTransaction();
        $adapter->rollbackTransaction();
    }

    public function testTransactionWasOpenOutside(): void
    {
        $dbAdapter = $this->dbAdapter;

        /** @var MockInterface&Pdo $adapterMock */
        $adapterMock = \Mockery::mock($this->adapterNestedDoNotSupport);
        $adapterMock->shouldReceive('canHandleNestedTransaction')
            ->andReturnFalse();

        $adapterMock->shouldReceive('beginTransaction')
            ->times(2);

        $adapterMock->shouldReceive('commitTransaction')
            ->times(1);

        $adapterMock->shouldReceive('rollbackTransaction')
            ->times(1);

        $adapter = new NestedTransactionDecorator($adapterMock);

        $dbAdapter->beginTransaction(); // start transaction outside

        $adapter->beginTransaction();
        $adapter->commitTransaction();

        $dbAdapter->rollBack(); // close transaction outside

        $adapter->beginTransaction();
        $adapter->rollbackTransaction();

        $adapter->beginTransaction();
        $adapter->commitTransaction();

        $dbAdapter->beginTransaction(); // start transaction outside

        $adapter->beginTransaction();
        $adapter->rollbackTransaction();

        $dbAdapter->rollBack(); // close transaction outside
    }
}
