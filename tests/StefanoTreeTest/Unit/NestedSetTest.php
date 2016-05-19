<?php
namespace StefanoTreeTest\Unit;

use StefanoTree\NestedSet;

class NestedSetTest
    extends \PHPUnit_Framework_TestCase
{
    public function factoryDataProvider()
    {
        return array(
            array(
                '\StefanoDb\Adapter\Adapter',
                '\StefanoTree\NestedSet\Adapter\Zend2DbAdapter',
            ),
            array(
                '\Doctrine\DBAL\Connection',
                '\StefanoTree\NestedSet\Adapter\Doctrine2DBALAdapter',
            ),
            array(
                '\Zend_Db_Adapter_Abstract',
                '\StefanoTree\NestedSet\Adapter\Zend1DbAdapter',
            ),
        );
    }

    /**
     * @dataProvider factoryDataProvider
     */
    public function testFactoryMethod($dbAdapterClass, $expectedAdapterClass)
    {
        $optionsStub = \Mockery::mock('\StefanoTree\NestedSet\Options');
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);

        $tree = NestedSet::factory($optionsStub, $dbAdapterStub);
        $adapter = $tree->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    public function testThrowExceptionIfYouDbAdapterIsNotSupporter()
    {
        $optionsStub = \Mockery::mock('\StefanoTree\NestedSet\Options');
        $dbAdapter = new \DateTime();

        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'Db adapter "DateTime" is not supported');

        NestedSet::factory($optionsStub, $dbAdapter);
    }
}
