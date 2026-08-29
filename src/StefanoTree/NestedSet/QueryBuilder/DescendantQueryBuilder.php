<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

use StefanoTree\NestedSet\Manipulator\ManipulatorInterface;
use StefanoTree\NestedSet\Utilities;

class DescendantQueryBuilder implements DescendantQueryBuilderInterface
{
    private int $excludeFirstNLevel = 0;
    private ?int $limitDepth = null;
    private int|string|null $excludeBranch = null;

    public function __construct(
        private readonly ManipulatorInterface $manipulator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function get(int|string $nodeId, bool $nested = false): array
    {
        $result = $this->getManipulator()
            ->getDescendants($nodeId, $this->excludeFirstNLevel, $this->limitDepth, $this->excludeBranch);

        return $nested
            ? Utilities::flatToNested($result, $this->getManipulator()->getOptions()->getLevelColumnName()) : $result;
    }

    /**
     * {@inheritdoc}
     */
    public function excludeFirstNLevel(int $count): DescendantQueryBuilderInterface
    {
        $this->excludeFirstNLevel = $count;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function levelLimit(int $count): DescendantQueryBuilderInterface
    {
        $this->limitDepth = $count;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function excludeBranch(int|string $nodeId): DescendantQueryBuilderInterface
    {
        $this->excludeBranch = $nodeId;

        return $this;
    }

    private function getManipulator(): ManipulatorInterface
    {
        return $this->manipulator;
    }
}
