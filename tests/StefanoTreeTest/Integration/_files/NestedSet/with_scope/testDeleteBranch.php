<?php

declare(strict_types=1);

return array(
    'tree_traversal_with_scope' => array(
        array(
            'tree_traversal_id' => 1,
            'name' => null,
            'lft' => 1,
            'rgt' => 2,
            'parent_id' => null,
            'level' => 0,
            'scope' => 2,
        ),
        array(
            'tree_traversal_id' => 6,
            'name' => null,
            'lft' => 1,
            'rgt' => 6,
            'parent_id' => null,
            'level' => 0,
            'scope' => 1,
        ),
        array(
            'tree_traversal_id' => 7,
            'name' => null,
            'lft' => 2,
            'rgt' => 5,
            'parent_id' => 6,
            'level' => 1,
            'scope' => 1,
        ),
        array(
            'tree_traversal_id' => 8,
            'name' => null,
            'lft' => 3,
            'rgt' => 4,
            'parent_id' => 7,
            'level' => 2,
            'scope' => 1,
        ),
    ),
);
