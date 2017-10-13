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
                \Zend\Db\Adapter\Adapter::class,
                \StefanoTree\NestedSet\Adapter\Zend2::class,
            ),
            array(
                \Doctrine\DBAL\Connection::class,
                \StefanoTree\NestedSet\Adapter\Doctrine2DBAL::class,
            ),
            array(
                \Zend_Db_Adapter_Abstract::class,
                \StefanoTree\NestedSet\Adapter\Zend1::class,
            ),
        );
    }

    /**
     * @dataProvider factoryDataProvider
     */
    public function testFactoryMethod($dbAdapterClass, $expectedAdapterClass)
    {
        $optionsStub = \Mockery::mock(\StefanoTree\NestedSet\Options::class);
        $dbAdapterStub = \Mockery::mock($dbAdapterClass);

        $tree = NestedSet::factory($optionsStub, $dbAdapterStub);
        $adapter = $tree->getAdapter();

        $this->assertInstanceOf($expectedAdapterClass, $adapter);
    }

    public function testThrowExceptionIfYourDbAdapterIsNotSupporter()
    {
        $optionsStub = \Mockery::mock(\StefanoTree\NestedSet\Options::class);
        $dbAdapter = new \DateTime();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Db adapter "DateTime" is not supported');

        NestedSet::factory($optionsStub, $dbAdapter);
    }
}
