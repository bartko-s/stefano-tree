<?php
namespace StefanoTree\NestedSet\Adapter;

class StefanoDb
    extends Zend2
{
    public function beginTransaction()
    {
        $this->getDbAdapter()
             ->begin();
    }

    public function commitTransaction()
    {
        $this->getDbAdapter()
             ->commit();
    }

    public function rollbackTransaction()
    {
        $this->getDbAdapter()
             ->rollback();
    }
}
