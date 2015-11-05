<?php
namespace StefanoTree\NestedSet\Adapter;


abstract class NestedTransactionDbAdapterAbstract
    implements AdapterInterface
{
    private $outerTransactionWasStarted = false;

    /**
     * Start DB transaction
     */
    abstract protected function _beginTransaction();

    /**
     * Commit DB transaction
     */
    abstract protected function _commitTransaction();

    /**
     * Rollback DB transaction
     */
    abstract protected function _rollbackTransaction();

    /**
     * Check if DB transaction has been started
     * @return boolean
     */
    abstract protected function _isInTransaction();

    public function beginTransaction() {
        $this->outerTransactionWasStarted = $this->_isInTransaction();

        if(!$this->outerTransactionWasStarted) {
            $this->_beginTransaction();
        }
    }

    public function commitTransaction() {
        if(!$this->outerTransactionWasStarted) {
            $this->_commitTransaction();
        }
    }

    public function rollbackTransaction() {
        if(!$this->outerTransactionWasStarted) {
            $this->_rollbackTransaction();
        }
    }
}