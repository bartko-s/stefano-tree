<?php
namespace StefanoTree;

use Exception;

class NodeInfo
{
    protected $_params = array(
        'id'        => null,
        'parentId'  => null,
        'level'     => null,
        'left'      => null,
        'right'     => null,
    );
    
    /**
     * @param array $params
     * @throws \Exception
     */
    public function __construct(array $params) {
        foreach(array_keys($this->_params) as $paramName) {
            if(!array_key_exists($paramName, $params)) {
                throw new Exception('Param with name"' . $paramName . '" must be set');
            } else {
                $this->_params[$paramName] = $params[$paramName];
            }
        }
    }
    
    public function getId() {
        return $this->_params['id'];
    }

    public function getParentId() {
        return $this->_params['parentId'];
    }

    public function getLevel() {
        return $this->_params['level'];
    }

    public function getLeft() {
        return $this->_params['left'];
    }

    public function getRight() {
        return $this->_params['right'];
    }
}
