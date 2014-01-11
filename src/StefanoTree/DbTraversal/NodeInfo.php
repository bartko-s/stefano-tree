<?php
namespace StefanoTree\DbTraversal;

use StefanoTree\Exception\InvalidArgumentException;

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
     * @throws InvalidArgumentException
     */
    public function __construct(array $params) {
        $missingParams = array();
        
        foreach(array_keys($this->_params) as $paramName) {
            if(!array_key_exists($paramName, $params)) {
                $missingParams[] = $paramName;                
            } else {
                $this->_params[$paramName] = $params[$paramName];
            }
        }
        
        if(0 < count($missingParams)) {
            throw new InvalidArgumentException(sprintf('Params "%s" must be set', 
                implode(', ', $missingParams)));
        }
    }
    
    /**
     * @return int
     */
    public function getId() {
        return $this->_params['id'];
    }

    /**
     * @return int|null
     */
    public function getParentId() {
        return $this->_params['parentId'];
    }

    /**
     * @return int
     */
    public function getLevel() {
        return $this->_params['level'];
    }

    /**
     * @return int
     */
    public function getLeft() {
        return $this->_params['left'];
    }

    /**
     * @return int
     */
    public function getRight() {
        return $this->_params['right'];
    }
}
