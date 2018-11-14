<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet;

class NodeInfo
{
    private $id;
    private $parentId;
    private $level;
    private $left;
    private $right;
    private $scope;

    /**
     * @param string|int|null $id
     * @param string|int|null $parentId
     * @param int             $level
     * @param int             $left
     * @param int             $right
     * @param string|int|null $scope    If scope is not used
     */
    public function __construct($id, $parentId, int $level, int $left, int $right, $scope)
    {
        $this->id = $id;
        $this->parentId = $parentId;
        $this->level = $level;
        $this->left = $left;
        $this->right = $right;
        $this->scope = $scope;
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|int|null
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $level
     */
    public function setLevel($level): void
    {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $left
     */
    public function setLeft($left): void
    {
        $this->left = $left;
    }

    /**
     * @return int
     */
    public function getLeft(): int
    {
        return $this->left;
    }

    /**
     * @param int $right
     */
    public function setRight($right): void
    {
        $this->right = $right;
    }

    /**
     * @return int
     */
    public function getRight(): int
    {
        return $this->right;
    }

    /**
     * @return string|int|null
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        if (null === $this->getParentId()) {
            return true;
        } else {
            return false;
        }
    }
}
