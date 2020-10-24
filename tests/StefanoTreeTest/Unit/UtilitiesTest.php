<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit;

use StefanoTree\NestedSet\Utilities;
use StefanoTreeTest\UnitTestCase;

/**
 * @internal
 * @coversNothing
 */
class UtilitiesTest extends UnitTestCase
{
    public function testFlatToNested()
    {
        $flatTree = array(
            array(
                'id' => '1',
                'parent' => null,
                'level' => 0,
            ),
            array(
                'id' => '1.1',
                'parent' => '1',
                'level' => 1,
            ),
            array(
                'id' => '1.2',
                'parent' => '1',
                'level' => 1,
            ),
        );
        $result = Utilities::flatToNested($flatTree);

        $this->assertEquals(
            array(
                array(
                    'id' => '1',
                    'parent' => null,
                    'level' => 0,
                    '_children' => array(
                        array(
                            'id' => '1.1',
                            'parent' => '1',
                            'level' => 1,
                            '_children' => array(),
                        ),
                        array(
                            'id' => '1.2',
                            'parent' => '1',
                            'level' => 1,
                            '_children' => array(),
                        ),
                    ),
                ),
            ),
            $result
        );
    }

    public function testFlatToNestedMultiRootNode()
    {
        $flatTree = array(
            array(
                'id' => '1',
                'parent' => null,
                'level' => 1,
            ),
            array(
                'id' => '1.2',
                'parent' => '1',
                'level' => 2,
            ),
            array(
                'id' => '2',
                'parent' => null,
                'level' => 1,
            ),
            array(
                'id' => '2.1',
                'parent' => '2',
                'level' => 2,
            ),
        );
        $result = Utilities::flatToNested($flatTree);

        $this->assertEquals(
            array(
                array(
                    'id' => '1',
                    'parent' => null,
                    'level' => 1,
                    '_children' => array(
                        array(
                            'id' => '1.2',
                            'parent' => '1',
                            'level' => 2,
                            '_children' => array(),
                        ),
                    ),
                ),
                array(
                    'id' => '2',
                    'parent' => null,
                    'level' => 1,
                    '_children' => array(
                        array(
                            'id' => '2.1',
                            'parent' => '2',
                            'level' => 2,
                            '_children' => array(),
                        ),
                    ),
                ),
            ),
            $result
        );
    }

    public function testFlatToNestedComplexTree()
    {
        $flatTree = array(
            array(
                'id' => '1',
                'parent' => null,
                'level' => 1,
            ),
            array(
                'id' => '1.2',
                'parent' => '1',
                'level' => 2,
            ),
            array(
                'id' => '1.2.1',
                'parent' => '1.2',
                'level' => 3,
            ),
            array(
                'id' => '1.2.1.1',
                'parent' => '1.2.1',
                'level' => 4,
            ),
            array(
                'id' => '2.1',
                'parent' => '1',
                'level' => 2,
            ),
            array(
                'id' => '2.1.1',
                'parent' => '2.1',
                'level' => 3,
            ),
            array(
                'id' => '2.1.2',
                'parent' => '2.1',
                'level' => 3,
            ),
            array(
                'id' => '2.1.3',
                'parent' => '2.1',
                'level' => 3,
            ),
            array(
                'id' => '2',
                'parent' => null,
                'level' => 1,
            ),
            array(
                'id' => '2.1',
                'parent' => '2',
                'level' => 2,
            ),
            array(
                'id' => '2.1.1',
                'parent' => '2.1',
                'level' => 3,
            ),
            array(
                'id' => '2.2',
                'parent' => '2',
                'level' => 2,
            ),
        );
        $result = Utilities::flatToNested($flatTree);

        $this->assertEquals(
            array(
                array(
                    'id' => '1',
                    'parent' => null,
                    'level' => 1,
                    '_children' => array(
                        array(
                            'id' => '1.2',
                            'parent' => '1',
                            'level' => 2,
                            '_children' => array(
                                array(
                                    'id' => '1.2.1',
                                    'parent' => '1.2',
                                    'level' => 3,
                                    '_children' => array(
                                        array(
                                            'id' => '1.2.1.1',
                                            'parent' => '1.2.1',
                                            'level' => 4,
                                            '_children' => array(),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'id' => '2.1',
                            'parent' => '1',
                            'level' => 2,
                            '_children' => array(
                                array(
                                    'id' => '2.1.1',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => array(),
                                ),
                                array(
                                    'id' => '2.1.2',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => array(),
                                ),
                                array(
                                    'id' => '2.1.3',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => array(),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'id' => '2',
                    'parent' => null,
                    'level' => 1,
                    '_children' => array(
                        array(
                            'id' => '2.1',
                            'parent' => '2',
                            'level' => 2,
                            '_children' => array(
                                array(
                                    'id' => '2.1.1',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => array(),
                                ),
                            ),
                        ),
                        array(
                            'id' => '2.2',
                            'parent' => '2',
                            'level' => 2,
                            '_children' => array(),
                        ),
                    ),
                ),
            ),
            $result
        );
    }

    public function testFlatToNestedChangeLevelName()
    {
        $flatTree = array(
            array(
                'id' => '1',
                'parent' => null,
                'lvl' => 0,
            ),
            array(
                'id' => '1.1',
                'parent' => '1',
                'lvl' => 1,
            ),
            array(
                'id' => '1.2',
                'parent' => '1',
                'lvl' => 1,
            ),
        );
        $result = Utilities::flatToNested($flatTree, 'lvl');

        $this->assertEquals(
            array(
                array(
                    'id' => '1',
                    'parent' => null,
                    'lvl' => 0,
                    '_children' => array(
                        array(
                            'id' => '1.1',
                            'parent' => '1',
                            'lvl' => 1,
                            '_children' => array(),
                        ),
                        array(
                            'id' => '1.2',
                            'parent' => '1',
                            'lvl' => 1,
                            '_children' => array(),
                        ),
                    ),
                ),
            ),
            $result
        );
    }
}
