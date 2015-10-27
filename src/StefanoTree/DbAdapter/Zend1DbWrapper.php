<?php
namespace StefanoTree\DbAdapter;

use StefanoNestedTransaction\Adapter\TransactionInterface;

class Zend1DbWrapper implements TransactionInterface
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $_zendDbAdapter;

    /**
     * @param \Zend_Db_Adapter_Abstract $zendDbAdapter
     */
    public function __construct(\Zend_Db_Adapter_Abstract $zendDbAdapter) {
        $this->_zendDbAdapter = $zendDbAdapter;
    }

    public function getInternalAdapterClass() {
        return get_class($this->_zendDbAdapter);
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    public function begin() {
        $this->_zendDbAdapter->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return void
     */
    public function commit() {
        $this->_zendDbAdapter->commit();
    }

    /**
     * Roolback transaction
     *
     * @return void
     */
    public function rollback() {
        $this->_zendDbAdapter->rollBack();
    }

    public function __call($name, $args) {
        if (method_exists($this->_zendDbAdapter, $name)) {
            return call_user_func_array(array(
                $this->_zendDbAdapter,
                $name,
            ), $args);
        }
        throw new \BadMethodCallException('There is no method ' . $name);
    }

}
