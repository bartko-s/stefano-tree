<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Utilities;

class AncestorQueryBuilder implements AncestorQueryBuilderInterface
{
    private $adapter;

    private $excludeFirstNLevel = 0;
    private $excludeLastNLevel = 0;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function get($nodeId, bool $nested = false): array
    {
        $result = $this->getAdapter()
            ->getAncestors($nodeId, $this->excludeFirstNLevel, $this->excludeLastNLevel);

        return $nested ?
            Utilities::flatToNested($result, $this->getAdapter()->getOptions()->getLevelColumnName()) : $result;
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

    private function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }
}
