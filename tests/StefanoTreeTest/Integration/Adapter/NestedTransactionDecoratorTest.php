<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Adapter\NestedTransactionDecorator;
use StefanoTree\NestedSet\Adapter\Pdo;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;
use StefanoTreeTest\UnitTestCase;
use Zend_Db_Adapter_Abstract as ZendDbAdapter;

/**
 * @internal
 * @coversNothing
 */
class NestedTransactionDecoratorTest extends UnitTestCase
{
    /**
     * @var AdapterInterface
     */
    protected $adapterNestedDoNotSupport;

    /**
     * @var ZendDbAdapter
     */
    protected $dbAdapter;

    protected function setUp()
    {
        $this->dbAdapter = TestUtil::getPDOConnection();

        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));

        $this->adapterNestedDoNotSupport = new Pdo($options, $this->dbAdapter);

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->adapterNestedDoNotSupport = null;
        parent::tearDown();
    }

    public function testCanHandleNestedTransaction()
    {
        $adapterStub = \Mockery::mock(AdapterInterface::class);

        $adapter = new NestedTransactionDecorator($adapterStub);
        $this->assertTrue($adapter->canHandleNestedTransaction());
    }

    public function testWrappedAdapterCanHandleHandleNestedTransaction()
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

    public function testHandleTransaction()
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

    public function testHandleBrokenTransaction()
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

    public function testRollbackOnlyMarkIsSetToFalseAfterRollbackSuccess()
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

    public function testHandleNestedTransaction()
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

    public function testHandleBrokenNestedTransaction()
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

    public function testBrokenTransactionIsRollbackOnly()
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

    public function testTransactionWasOpenOutside()
    {
        $dbAdapter = $this->dbAdapter;

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
