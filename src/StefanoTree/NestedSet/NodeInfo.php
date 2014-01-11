<?php
namespace StefanoTree\NestedSet;

class NodeInfo
{
    private $id;
    private $parentId;
    private $level;
    private $left;
    private $right;
 
    public function __construct($id, $parentId, $level, $left, $right) {
        $this->id       = $id;
        $this->parentId = $parentId;
        $this->level    = $level;
        $this->left     = $left;
        $this->right    = $right;
    }   
    
    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**
     * @return int|null
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @return int|null
     */
    public function getLeft() {
        return $this->left;
    }

    /**
     * @return int|null
     */
    public function getRight() {
        return $this->right;
    }
}
