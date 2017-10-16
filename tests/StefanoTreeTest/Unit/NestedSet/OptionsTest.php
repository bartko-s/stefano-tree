<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit\NestedSet;

use StefanoTree\NestedSet\Options;
use StefanoTreeTest\UnitTestCase;

class NestedSetTest extends UnitTestCase
{
    public function testThrowExceptionIfAllRequiredSettingsAreNotProvided()
    {
        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('tableName, idColumnName must be set');

        new Options(array());
    }

    /**
     * @return \StefanoTree\NestedSet\Options
     */
    private function getOptionsWithDefaultSettings()
    {
        return new Options(array(
            'tableName' => 'table',
            'idColumnName' => 'id',
        ));
    }

    public function testThrowExceptionIfTrySetWrongTableName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('tableName cannot be empty');

        $optionsStub->setTableName(' ');
    }

    public function testThrowExceptionIfTrySetWrongIdColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('idColumnName cannot be empty');

        $optionsStub->setIdColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongLeftColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('leftColumnName cannot be empty');

        $optionsStub->setLeftColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongRightColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('rightColumnName cannot be empty');

        $optionsStub->setRightColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongLevelColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('levelColumnName cannot be empty');

        $optionsStub->setLevelColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongParentIdColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(\StefanoTree\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('parentIdColumnName cannot be empty');

        $optionsStub->setParentIdColumnName(' ');
    }

    public function testGetTableName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setTableName('   table ');

        $this->assertEquals('table', $optionsStub->getTableName());
    }

    public function testGetIdColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setIdColumnName('   id ');

        $this->assertEquals('id', $optionsStub->getIdColumnName());
    }

    public function testGetLeftColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('lft', $optionsStub->getLeftColumnName(), 'Wrong default value');

        $optionsStub->setLeftColumnName('   left ');

        $this->assertEquals('left', $optionsStub->getLeftColumnName());
    }

    public function testGetRightColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('rgt', $optionsStub->getRightColumnName(), 'Wrong default value');

        $optionsStub->setRightColumnName('   right ');

        $this->assertEquals('right', $optionsStub->getRightColumnName());
    }

    public function testGetLevelColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('level', $optionsStub->getLevelColumnName(), 'Wrong default value');

        $optionsStub->setLevelColumnName('   lvl ');

        $this->assertEquals('lvl', $optionsStub->getLevelColumnName());
    }

    public function testGetParentIdColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('parent_id', $optionsStub->getParentIdColumnName(), 'Wrong default value');

        $optionsStub->setParentIdColumnName('   prt ');

        $this->assertEquals('prt', $optionsStub->getParentIdColumnName());
    }

    public function testGetSequenceName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('', $optionsStub->getSequenceName(), 'Wrong default value');

        $optionsStub->setSequenceName('   seq ');

        $this->assertEquals('seq', $optionsStub->getSequenceName());
    }

    public function testGetDefaultScopeColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('', $optionsStub->getScopeColumnName());
    }

    public function testSetScopeColumnName()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setScopeColumnName('   scope   ');

        $this->assertEquals('scope', $optionsStub->getScopeColumnName());
    }

    public function testGetDefaultTableAlias()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();
        $this->assertEquals('t', $optionsStub->getTableAlias());
    }

    public function testSetTableAlias()
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setTableAlias('   tableAlias   ');

        $this->assertEquals('tableAlias', $optionsStub->getTableAlias());
    }
}
