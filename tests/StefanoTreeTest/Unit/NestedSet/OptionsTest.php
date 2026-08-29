<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit\NestedSet;

use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\UnitTestCase;

/**
 * @internal
 */
class OptionsTest extends UnitTestCase
{
    public function testThrowExceptionIfAllRequiredSettingsAreNotProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tableName, idColumnName must be set');

        new Options(array());
    }

    private function getOptionsWithDefaultSettings(): Options
    {
        return new Options(array(
            'tableName' => 'table',
            'idColumnName' => 'id',
        ));
    }

    public function testThrowExceptionIfTrySetWrongTableName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tableName cannot be empty');

        $optionsStub->setTableName(' ');
    }

    public function testThrowExceptionIfTrySetWrongIdColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('idColumnName cannot be empty');

        $optionsStub->setIdColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongLeftColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('leftColumnName cannot be empty');

        $optionsStub->setLeftColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongRightColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('rightColumnName cannot be empty');

        $optionsStub->setRightColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongLevelColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('levelColumnName cannot be empty');

        $optionsStub->setLevelColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongParentIdColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('parentIdColumnName cannot be empty');

        $optionsStub->setParentIdColumnName(' ');
    }

    public function testGetTableName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setTableName('   table ');

        $this->assertEquals('table', $optionsStub->getTableName());
    }

    public function testGetIdColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setIdColumnName('   id ');

        $this->assertEquals('id', $optionsStub->getIdColumnName());
    }

    public function testGetLeftColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('lft', $optionsStub->getLeftColumnName(), 'Wrong default value');

        $optionsStub->setLeftColumnName('   left ');

        $this->assertEquals('left', $optionsStub->getLeftColumnName());
    }

    public function testGetRightColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('rgt', $optionsStub->getRightColumnName(), 'Wrong default value');

        $optionsStub->setRightColumnName('   right ');

        $this->assertEquals('right', $optionsStub->getRightColumnName());
    }

    public function testGetLevelColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('level', $optionsStub->getLevelColumnName(), 'Wrong default value');

        $optionsStub->setLevelColumnName('   lvl ');

        $this->assertEquals('lvl', $optionsStub->getLevelColumnName());
    }

    public function testGetParentIdColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('parent_id', $optionsStub->getParentIdColumnName(), 'Wrong default value');

        $optionsStub->setParentIdColumnName('   prt ');

        $this->assertEquals('prt', $optionsStub->getParentIdColumnName());
    }

    public function testGetDefaultScopeColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('', $optionsStub->getScopeColumnName());
    }

    public function testSetScopeColumnName(): void
    {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setScopeColumnName('   scope   ');

        $this->assertEquals('scope', $optionsStub->getScopeColumnName());
    }
}
