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
    public function excludeFistNLevel(int $count): AncestorQueryBuilderInterface;

    /**
     * Exclude last node from result.
     *
     * @return AncestorQueryBuilderInterface
     */
    public function excludeLastLevel(): AncestorQueryBuilderInterface;
}
