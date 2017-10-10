<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit;

use StefanoTree\NestedSet;
use StefanoTreeTest\UnitTestCase;

class NestedSetTest extends UnitTestCase
{
    public function factoryDataProvider()
    {
        return array(
            array(
                '\Zend\Db\Adapter\Adapter',
                '\StefanoTree\NestedSet\Adapter\Zend2',
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

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Db adapter "DateTime" is not supported');

        NestedSet::factory($optionsStub, $dbAdapter);
    }
}
