<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTreeTest\IntegrationTestCase;

abstract class AdapterAbstract extends IntegrationTestCase
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    protected $adapterCanQuoteIdentifier = true;

    protected function setUp(): void
    {
        $this->adapter = null;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->adapter = null;
        parent::tearDown();
    }

    /**
     * @return AdapterInterface
     */
    abstract protected function getAdapter(): AdapterInterface;

    protected function getDataSet()
    {
        return $this->createArrayDataSet(include __DIR__.'/../_files/NestedSet/initDataSet.php');
    }

    public function testIsInTransaction()
    {
        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->isInTransaction());
        $adapter->beginTransaction();
        $this->assertTrue($adapter->isInTransaction());
        $adapter->rollbackTransaction();
    }

    public function testCommitTransaction()
    {
        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->isInTransaction());
        $adapter->beginTransaction();
        $this->assertTrue($adapter->isInTransaction());
        $adapter->commitTransaction();
        $this->assertFalse($adapter->isInTransaction());
    }

    public function testRollbackTransaction()
    {
        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->isInTransaction());
        $adapter->beginTransaction();
        $this->assertTrue($adapter->isInTransaction());
        $adapter->rollbackTransaction();
        $this->assertFalse($adapter->isInTransaction());
    }

    public function testHandleNestedTransaction()
    {
        $adapter = $this->getAdapter();
        if (!$adapter->canHandleNestedTransaction()) {
            $this->markTestSkipped('Adapter does not support nested transaction');

            return;
        }
        $adapter->beginTransaction();
        $this->assertTrue($adapter->isInTransaction());
        $adapter->beginTransaction();
        $adapter->commitTransaction();
        $this->assertTrue($adapter->isInTransaction());
        $adapter->commitTransaction();
        $this->assertFalse($adapter->isInTransaction());
    }

    public function testQuoteIdentifier()
    {
        $a = $this->getAdapter();

        if ($this->adapterCanQuoteIdentifier) {
            if ('pgsql' == TEST_STEFANO_DB_VENDOR) {
                $this->assertEquals(
                    '"simple"',
                    $a->quoteIdentifier('simple')
                );

                $this->assertEquals(
                    '"more"."complex"',
                    $a->quoteIdentifier('more.complex')
                );
            } else {
                $this->assertEquals(
                    '`simple`',
                    $a->quoteIdentifier('simple')
                );

                $this->assertEquals(
                    '`more`.`complex`',
                    $a->quoteIdentifier('more.complex')
                );
            }
        } else {
            $this->assertEquals(
                'more.complex',
                $a->quoteIdentifier('more.complex')
            );
        }
    }

    public function testExecuteInsertSQL()
    {
        $a = $this->getAdapter();

        $sql = 'INSERT INTO tree_traversal (name, lft, rgt, parent_id, level)'
            .' VALUES(:name, :lft, :rgt, :parent_id, :level)';

        $data = array(
            'name' => 'ahoj',
            'lft' => 123,
            'rgt' => 456,
            'parent_id' => 10,
            'level' => 789,
        );

        $id = $a->executeInsertSQL($sql, $data);
        $this->assertEquals(26, $id);

        $result = $a->executeSelectSQL('SELECT * FROM tree_traversal WHERE tree_traversal_id = 26');
        $this->assertEquals(
            array(
                array_merge($data, array('tree_traversal_id' => 26)),
            ),
            $result
        );
    }

    public function testExecuteSQL()
    {
        $a = $this->getAdapter();

        $sql = 'UPDATE tree_traversal SET name = :name WHERE tree_traversal_id = :id';

        $data = array(
            'name' => 'testujeme parne valce',
            'id' => 10,
        );

        $a->executeSQL($sql, $data);

        $result = $a->executeSelectSQL(
            'SELECT tree_traversal_id AS id, name FROM tree_traversal WHERE tree_traversal_id = :id',
            array('id' => $data['id'])
        );

        $this->assertEquals(
            array(
                $data,
            ),
            $result
        );
    }
}
