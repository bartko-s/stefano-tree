<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

interface AncestorQueryBuilderInterface
{
    /**
     * Execute query and return result.
     *
     * @param int|string $nodeId
     * @param bool       $nested Return result as nested array instead flat array
     *
     * @return array
     */
    public function get($nodeId, bool $nested = false): array;

    /**
     * Limit number of levels.
     *
     * @param int $count
     *
     * @return AncestorQueryBuilderInterface
     */
    public function excludeFirstNLevel(int $count): self;

    /**
     * Exclude last node from result.
     *
     * @param int $count
     *
     * @return AncestorQueryBuilderInterface
     */
    public function excludeLastNLevel(int $count): self;
}
