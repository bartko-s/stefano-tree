<?php

declare(strict_types=1);

return array(
    'tree_traversal_with_scope' => array(
        array(
            'tree_traversal_id' => 1,
            'name' => null,
            'lft' => 1,
            'rgt' => 10,
            'parent_id' => null,
            'level' => 0,
            'scope' => 2,
        ),
        array(
            'tree_traversal_id' => 6,
            'name' => null,
            'lft' => 1,
            'rgt' => 10,
            'parent_id' => null,
            'level' => 0,
            'scope' => 1,
        ),
        array(
            'tree_traversal_id' => 7,
            'name' => null,
            'lft' => 2,
            'rgt' => 9,
            'parent_id' => 6,
            'level' => 1,
            'scope' => 1,
        ),
        array(
            'tree_traversal_id' => 8,
            'name' => null,
            'lft' => 3,
            'rgt' => 8,
            'parent_id' => 7,
            'level' => 2,
            'scope' => 1,
        ),
        array(
            'tree_traversal_id' => 9,
            'name' => null,
            'lft' => 4,
            'rgt' => 7,
            'parent_id' => 8,
            'level' => 3,
            'scope' => 1,
        ),
        array(
            'tree_traversal_id' => 10,
            'name' => null,
            'lft' => 5,
            'rgt' => 6,
            'parent_id' => 9,
            'level' => 4,
            'scope' => 1,
        ),
    ),
);
