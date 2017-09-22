<?php

namespace StefanoTreeTest\Unit;

use StefanoTree\NestedSet;

class NestedSetTest extends \PHPUnit_Framework_TestCase
{
    public function factoryDataProvider()
    {
        return array(
            array(
                '\Zend\Db\Adapter\Adapter',
                '\StefanoTree\NestedSet\Adapter\Zend2',
            ),
            array(
                '\StefanoDb\Adapter\Adapter',
                '\StefanoTree\NestedSet\Adapter\StefanoDb',
            ),
            array(
                '\Doctrine\DBAL\Connection',
                '\StefanoTree\NestedSet\Adapter\Doctrine2DBAL',
            ),
            array(
                '\Zend_Db_Adapter_Abstract',
                '\StefanoTree\NestedSet\Adapter\Zend1',
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
