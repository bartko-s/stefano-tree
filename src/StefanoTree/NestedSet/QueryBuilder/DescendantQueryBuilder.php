<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\QueryBuilder;

use StefanoTree\NestedSet\Adapter\AdapterInterface;

class DescendantQueryBuilder implements DescendantQueryBuilderInterface
{
    private $adapter;

    private $excludeFirstNLevel = 0;
    private $limitDepth = null;
    private $excludeBranch = null;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function get($nodeId): array
    {
        return $this->getAdapter()
            ->getDescendants($nodeId, $this->excludeFirstNLevel, $this->limitDepth, $this->excludeBranch);
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
    public function excludeBranch($nodeId): DescendantQueryBuilderInterface
    {
        $this->excludeBranch = $nodeId;

        return $this;
    }

    private function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }
}
