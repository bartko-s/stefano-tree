<?php
namespace StefanoTreeTest\Unit\Adapter\DbTraversable;

use StefanoTree\Adapter\DbTraversal\Options;

class DbTraversalTest
    extends \PHPUnit_Framework_TestCase
{
    public function testThrowExceptionIfAllRequiredSettingsAreNotProvided() {
        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'tableName, idColumnName must be set');

        new Options(array());
    }

    /**
     * @return \StefanoTree\Adapter\DbTraversal\Options
     */
    private function getOptionsWithDefaultSettings() {
        return new Options(array(
            'tableName' => 'table',
            'idColumnName' => 'id',
            'dbAdapter' => \Mockery::mock('\StefanoDb\Adapter\Adapter'),
        ));
    }

    public function testThrowExceptionIfTrySetWrongTableName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'tableName cannot be empty');

        $optionsStub->setTableName(' ');
    }

    public function testThrowExceptionIfTrySetWrongIdColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'idColumnName cannot be empty');

        $optionsStub->setIdColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongLeftColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'leftColumnName cannot be empty');

        $optionsStub->setLeftColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongRightColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'rightColumnName cannot be empty');

        $optionsStub->setRightColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongLevelColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'levelColumnName cannot be empty');

        $optionsStub->setLevelColumnName(' ');
    }

    public function testThrowExceptionIfTrySetWrongParentIdColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'parentIdColumnName cannot be empty');

        $optionsStub->setParentIdColumnName(' ');
    }

    public function testGetTableName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setTableName('   table ');

        $this->assertEquals('table', $optionsStub->getTableName());
    }

    public function testGetIdColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $optionsStub->setIdColumnName('   id ');

        $this->assertEquals('id', $optionsStub->getIdColumnName());
    }

    public function testGetLeftColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('lft', $optionsStub->getLeftColumnName(), 'Wrong default value');

        $optionsStub->setLeftColumnName('   left ');

        $this->assertEquals('left', $optionsStub->getLeftColumnName());
    }

    public function testGetRightColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('rgt', $optionsStub->getRightColumnName(), 'Wrong default value');

        $optionsStub->setRightColumnName('   right ');

        $this->assertEquals('right', $optionsStub->getRightColumnName());
    }

    public function testGetLevelColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('level', $optionsStub->getLevelColumnName(), 'Wrong default value');

        $optionsStub->setLevelColumnName('   lvl ');

        $this->assertEquals('lvl', $optionsStub->getLevelColumnName());
    }

    public function testGetParentIdColumnName() {
        $optionsStub = $this->getOptionsWithDefaultSettings();

        $this->assertEquals('parent_id', $optionsStub->getParentIdColumnName(), 'Wrong default value');

        $optionsStub->setParentIdColumnName('   prt ');

        $this->assertEquals('prt', $optionsStub->getParentIdColumnName());
    }
}