<?php
namespace StefanoTreeTest\Integration;

use StefanoTree\DbAdapter\Zend1DbWrapper;
use StefanoTree\NestedSet as TreeAdapter;
use StefanoTree\NestedSet\Adapter\Zend1DbAdapter;
use StefanoTree\NestedSet\Options;

class NestedSetWithZend1DbAdapterTest
    extends AbstractTest
{
    protected function getTreeAdapter() {
        $dbAdapter = \Zend_Db::factory('Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER), array(
            'hostname' => TEST_STEFANO_DB_HOSTNAME,
            'dbname' => TEST_STEFANO_DB_DB_NAME,
            'username' => TEST_STEFANO_DB_USER,
            'password' => TEST_STEFANO_DB_PASSWORD
        ));

        $adapter = new Zend1DbWrapper($dbAdapter);

        $options = new Options(array(
            'tableName' => 'tree_traversal',
            'idColumnName' => 'tree_traversal_id',
        ));
        return new TreeAdapter(new Zend1DbAdapter($options, $adapter));
    }

    public function testSpecialSelect() {
        $this->getDataSet();
        /** @var Zend1DbAdapter $adapter */
        $adapter = $this->treeAdapter->getAdapter();

        $adapter->getDbAdapter()->insert('tree_traversal', [
            'name' => 'test',
            'unrelated_id' => '33',
        ]);
        $select = $adapter->getDefaultDbSelect();
        $select->where('unrelated_id != ?', 33);
        $adapter->setDefaultDbSelect($select);
        $data = array(
            'name' => 'ahoj',
            'unrelated_id' => 1
        );
        $return = $this->treeAdapter
            ->addNodePlacementBottom(1);
        $lastGeneratedValue = $this->treeAdapter
            ->addNodePlacementBottom(12);
        $lastGeneratedValue = $this->treeAdapter
            ->addNodePlacementBottom(19, $data);

        $dataSet = $this->getConnection()->createDataSet(array('tree_traversal'));
        $expectedDataSet = $this->createMySQLXMLDataSet(__DIR__ . '/_files/NestedSet/testAddNodePlacementBottom-2-custom.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
        $this->assertEquals(28, $lastGeneratedValue);
    }
}
