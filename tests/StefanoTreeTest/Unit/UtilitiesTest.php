<?php

declare(strict_types=1);

namespace StefanoTreeTest\Unit;

use StefanoTree\NestedSet\Utilities;
use StefanoTreeTest\UnitTestCase;

class UtilitiesTest extends UnitTestCase
{
    public function testFlatToNested()
    {
        $flatTree = [
            [
                'id' => '1',
                'parent' => null,
                'level' => 0,
            ],
            [
                'id' => '1.1',
                'parent' => '1',
                'level' => 1,
            ],
            [
                'id' => '1.2',
                'parent' => '1',
                'level' => 1,
            ],
        ];
        $result = Utilities::flatToNested($flatTree);

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent' => null,
                    'level' => 0,
                    '_children' => [
                        [
                            'id' => '1.1',
                            'parent' => '1',
                            'level' => 1,
                            '_children' => [],
                        ],
                        [
                            'id' => '1.2',
                            'parent' => '1',
                            'level' => 1,
                            '_children' => [],
                        ],
                    ],
                ],
            ], $result);
    }

    public function testFlatToNestedMultiRootNode()
    {
        $flatTree = [
            [
                'id' => '1',
                'parent' => null,
                'level' => 1,
            ],
            [
                'id' => '1.2',
                'parent' => '1',
                'level' => 2,
            ],
            [
                'id' => '2',
                'parent' => null,
                'level' => 1,
            ],
            [
                'id' => '2.1',
                'parent' => '2',
                'level' => 2,
            ],
        ];
        $result = Utilities::flatToNested($flatTree);

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent' => null,
                    'level' => 1,
                    '_children' => [
                        [
                            'id' => '1.2',
                            'parent' => '1',
                            'level' => 2,
                            '_children' => [],
                        ],
                    ],
                ],
                [
                    'id' => '2',
                    'parent' => null,
                    'level' => 1,
                    '_children' => [
                        [
                            'id' => '2.1',
                            'parent' => '2',
                            'level' => 2,
                            '_children' => [],
                        ],
                    ],
                ],
            ], $result);
    }

    public function testFlatToNestedComplexTree()
    {
        $flatTree = [
            [
                'id' => '1',
                'parent' => null,
                'level' => 1,
            ],
            [
                'id' => '1.2',
                'parent' => '1',
                'level' => 2,
            ],
            [
                'id' => '1.2.1',
                'parent' => '1.2',
                'level' => 3,
            ],
            [
                'id' => '1.2.1.1',
                'parent' => '1.2.1',
                'level' => 4,
            ],
            [
                'id' => '2.1',
                'parent' => '1',
                'level' => 2,
            ],
            [
                'id' => '2.1.1',
                'parent' => '2.1',
                'level' => 3,
            ],
            [
                'id' => '2.1.2',
                'parent' => '2.1',
                'level' => 3,
            ],
            [
                'id' => '2.1.3',
                'parent' => '2.1',
                'level' => 3,
            ],
            [
                'id' => '2',
                'parent' => null,
                'level' => 1,
            ],
            [
                'id' => '2.1',
                'parent' => '2',
                'level' => 2,
            ],
            [
                'id' => '2.1.1',
                'parent' => '2.1',
                'level' => 3,
            ],
            [
                'id' => '2.2',
                'parent' => '2',
                'level' => 2,
            ],
        ];
        $result = Utilities::flatToNested($flatTree);

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent' => null,
                    'level' => 1,
                    '_children' => [
                        [
                            'id' => '1.2',
                            'parent' => '1',
                            'level' => 2,
                            '_children' => [
                                [
                                    'id' => '1.2.1',
                                    'parent' => '1.2',
                                    'level' => 3,
                                    '_children' => [
                                        [
                                            'id' => '1.2.1.1',
                                            'parent' => '1.2.1',
                                            'level' => 4,
                                            '_children' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => '2.1',
                            'parent' => '1',
                            'level' => 2,
                            '_children' => [
                                [
                                    'id' => '2.1.1',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => [],
                                ],
                                [
                                    'id' => '2.1.2',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => [],
                                ],
                                [
                                    'id' => '2.1.3',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => '2',
                    'parent' => null,
                    'level' => 1,
                    '_children' => [
                        [
                            'id' => '2.1',
                            'parent' => '2',
                            'level' => 2,
                            '_children' => [
                                [
                                    'id' => '2.1.1',
                                    'parent' => '2.1',
                                    'level' => 3,
                                    '_children' => [],
                                ],
                            ],
                        ],
                        [
                            'id' => '2.2',
                            'parent' => '2',
                            'level' => 2,
                            '_children' => [],
                        ],
                    ],
                ],
            ], $result);
    }

    public function testFlatToNestedChangeLevelName()
    {
        $flatTree = [
            [
                'id' => '1',
                'parent' => null,
                'lvl' => 0,
            ],
            [
                'id' => '1.1',
                'parent' => '1',
                'lvl' => 1,
            ],
            [
                'id' => '1.2',
                'parent' => '1',
                'lvl' => 1,
            ],
        ];
        $result = Utilities::flatToNested($flatTree, 'lvl');

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent' => null,
                    'lvl' => 0,
                    '_children' => [
                        [
                            'id' => '1.1',
                            'parent' => '1',
                            'lvl' => 1,
                            '_children' => [],
                        ],
                        [
                            'id' => '1.2',
                            'parent' => '1',
                            'lvl' => 1,
                            '_children' => [],
                        ],
                    ],
                ],
            ], $result);
    }
}
