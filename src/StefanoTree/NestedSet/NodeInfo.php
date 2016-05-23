<?php
namespace StefanoTree\NestedSet;

class NodeInfo
{
    private $id;
    private $parentId;
    private $level;
    private $left;
    private $right;
    private $scope;

    public function __construct($id, $parentId, $level, $left, $right, $scope=null)
    {
        $this->id       = $id;
        $this->parentId = $parentId;
        $this->level    = $level;
        $this->left     = $left;
        $this->right    = $right;
        $this->scope    = $scope;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @return int|null
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return int|null
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @return int|null
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @return null|int
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        if (0 == $this->getParentId()) {
            return true;
        } else {
            return false;
        }
    }
}
