<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

use StefanoTree\NestedSet\Manipulator\ManipulatorInterface;
use StefanoTree\NestedSet\Utilities;

class AncestorQueryBuilder implements AncestorQueryBuilderInterface
{
    private $manipulator;

    private $excludeFirstNLevel = 0;
    private $excludeLastNLevel = 0;

    /**
     * @param ManipulatorInterface $manipulator
     */
    public function __construct(ManipulatorInterface $manipulator)
    {
        $this->manipulator = $manipulator;
    }

    public function get($nodeId, bool $nested = false): array
    {
        $result = $this->getManipulator()
            ->getAncestors($nodeId, $this->excludeFirstNLevel, $this->excludeLastNLevel);

        return $nested ?
            Utilities::flatToNested($result, $this->getManipulator()->getOptions()->getLevelColumnName()) : $result;
    }

    public function excludeFirstNLevel(int $count): AncestorQueryBuilderInterface
    {
        $this->excludeFirstNLevel = $count;

        return $this;
    }

    public function excludeLastNLevel(int $count): AncestorQueryBuilderInterface
    {
        $this->excludeLastNLevel = $count;

        return $this;
    }

    private function getManipulator(): ManipulatorInterface
    {
        return $this->manipulator;
    }
}
