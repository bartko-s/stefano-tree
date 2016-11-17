<?php
namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface as TreeAdapterInterface;
use StefanoTree\NestedSet\Adapter\StefanoDb as NestedSetAdapter;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;


class StefanoDbTest
    extends AdapterTestAbstract
{
    /**
     * @return TreeAdapterInterface
     */
    protected function getAdapter()
    {
        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));

        if ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $options->setSequenceName('tree_traversal_tree_traversal_id_seq');
        }

        return new NestedSetAdapter($options, TestUtil::getStefanoDbAdapter());
    }

    public function testNestedTransactionCannotFail()
    {
        $adapter = $this->getAdapter();
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->commitTransaction();
        $adapter->commitTransaction();
    }

    public function testNestedTransactionCannotFail2()
    {
        $adapter = $this->getAdapter();
        $adapter->beginTransaction();
        $adapter->beginTransaction();
        $adapter->rollbackTransaction();
        $adapter->rollbackTransaction();
    }
}
