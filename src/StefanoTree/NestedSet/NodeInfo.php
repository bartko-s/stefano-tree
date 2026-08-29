<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet;

class NodeInfo
{
    public function __construct(
        private readonly int|string|null $id,
        private readonly int|string|null $parentId,
        private int $level,
        private int $left,
        private int $right,
        private readonly int|string|null $scope
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getParentId(): int|string|null
    {
        return $this->parentId;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLeft(int $left): void
    {
        $this->left = $left;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setRight(int $right): void
    {
        $this->right = $right;
    }

    public function getRight(): int
    {
        return $this->right;
    }

    public function getScope(): int|string|null
    {
        return $this->scope;
    }

    public function isRoot(): bool
    {
        if (null === $this->getParentId()) {
            return true;
        } else {
            return false;
        }
    }
}
