<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

interface AncestorQueryBuilderInterface
{
    /**
     * Execute query and return result.
     *
     * @param int|string $nodeId
     *
     * @return array
     */
    public function get($nodeId): array;

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
