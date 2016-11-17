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

    /**
     * NodeInfo constructor.
     * @param $id int
     * @param $parentId int
     * @param $level int
     * @param $left int
     * @param $right int
     * @param $scope null|int if scope is not used
     */
    public function __construct($id, $parentId, $level, $left, $right, $scope)
    {
        $this->id       = $id;
        $this->parentId = $parentId;
        $this->level    = $level;
        $this->left     = $left;
        $this->right    = $right;
        $this->scope    = $scope;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param $level int
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param $left int
     */
    public function setLeft($left)
    {
        $this->left = $left;
    }

    /**
     * @return int
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param $right int
     */
    public function setRight($right)
    {
        $this->right = $right;
    }

    /**
     * @return int
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @return int|null
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
