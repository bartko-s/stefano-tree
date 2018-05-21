<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

interface DescendantQueryBuilderInterface
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
     * Exclude fist N level from result.
     *
     * @param int $count
     *
     * @return DescendantQueryBuilderInterface
     */
    public function excludeFirstNLevel(int $count): self;

    /**
     * Limit number of levels.
     *
     * @param int $count
     *
     * @return DescendantQueryBuilderInterface
     */
    public function levelLimit(int $count): self;

    /**
     * Exclude specified branch from result.
     *
     * @param int|string $nodeId
     *
     * @return DescendantQueryBuilderInterface
     */
    public function excludeBranch($nodeId): self;
}
