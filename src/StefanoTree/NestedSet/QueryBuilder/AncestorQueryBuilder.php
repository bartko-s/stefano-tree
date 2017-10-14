<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

use StefanoTree\NestedSet\Adapter\AdapterInterface;

class AncestorQueryBuilder implements AncestorQueryBuilderInterface
{
    private $adapter;

    private $excludeFirstNLevel = 0;
    private $excludeLastLevel = false;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function get($nodeId): array
    {
        return $this->getAdapter()
            ->getAncestors($nodeId, $this->excludeFirstNLevel, $this->excludeLastLevel);
    }

    public function excludeFistNLevel(int $count): AncestorQueryBuilderInterface
    {
        $this->excludeFirstNLevel = $count;

        return $this;
    }

    public function excludeLastLevel(): AncestorQueryBuilderInterface
    {
        $this->excludeLastLevel = true;

        return $this;
    }

    private function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }
}
