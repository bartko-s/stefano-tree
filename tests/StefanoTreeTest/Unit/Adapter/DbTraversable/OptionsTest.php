<?php
namespace StefanoTreeTest\Unit\Adapter\DbTraversable;

use StefanoTree\Adapter\DbTraversal\Options;

class DbTraversalTest
    extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    private function getValidOptions() {
        return array(
            'tableName' => 'table',
            'idColumnName' => 'id',
            'dbAdapter' => $dbAdapterdStub = \Mockery::mock('\StefanoDb\Adapter\Adapter'),
        );
    }

    public function testThrowExceptionIfAllRequiredSettingsAreNotProvided() {
        $this->setExpectedException('\StefanoTree\Exception\InvalidArgumentException',
            'tableName, idColumnName, dbAdapter must be set');

        new Options(array());
    }

    public function objectConstructorOptionsDataProvider() {
        return array(
            array(
                '\StefanoTree\Exception\InvalidArgumentException',
                'tableName cannot be empty',
                'tableName',
                ' ',
            ),
            array(
                '\StefanoTree\Exception\InvalidArgumentException',
                'idColumnName cannot be empty',
                'idColumnName',
                ' ',
            ),
            array(
                '\StefanoTree\Exception\InvalidArgumentException',
                'leftColumnName cannot be empty',
                'leftColumnName',
                ' ',
            ),
            array(
                '\StefanoTree\Exception\InvalidArgumentException',
                'rightColumnName cannot be empty',
                'rightColumnName',
                ' ',
            ),
            array(
                '\StefanoTree\Exception\InvalidArgumentException',
                'levelColumnName cannot be empty',
                'levelColumnName',
                ' ',
            ),
            array(
                '\StefanoTree\Exception\InvalidArgumentException',
                'parentIdColumnName cannot be empty',
                'parentIdColumnName',
                ' ',
            ),
        );
    }

    /**
     * @dataProvider objectConstructorOptionsDataProvider
     */
    public function testThrowExceptionWrongConstructorOptions(
        $expectedException,
        $expectedMessage,
        $optinsKey,
        $optionsValue
        ) {
        $options = $this->getValidOptions();
        $options[$optinsKey] = $optionsValue;

        $this->setExpectedException($expectedException, $expectedMessage);

        new Options($options);
    }

    public function testDefaultValues() {
        $options = new Options($this->getValidOptions());

        $this->assertEquals('lft', $options->getLeftColumnName());
        $this->assertEquals('rgt', $options->getRightColumnName());
        $this->assertEquals('level', $options->getLevelColumnName());
        $this->assertEquals('parent_id', $options->getParentIdColumnName());
    }
}