<?php

namespace StefanoTree\NestedSet\Adapter;

use StefanoDb\Adapter\Adapter as StefanoDbAdapter;
use StefanoTree\Exception\InvalidArgumentException;

class StefanoDb extends Zend2
{
    /**
     * @param StefanoDbAdapter $dbAdapter
     *
     * @throws InvalidArgumentException
     */
    protected function setDbAdapter($dbAdapter)
    {
        if (!$dbAdapter instanceof StefanoDbAdapter) {
            throw new InvalidArgumentException(
                'DbAdapter must be instance of "%s" but instance of "%s" was given', StefanoDbAdapter::class, get_class($dbAdapter)
            );
        }

        parent::setDbAdapter($dbAdapter);
    }

    /**
     * @return StefanoDbAdapter
     *
     * @throws InvalidArgumentException
     */
    protected function getDbAdapter()
    {
        $dbAdapter = parent::getDbAdapter();
        if (!$dbAdapter instanceof StefanoDbAdapter) {
            throw new InvalidArgumentException(
                'DbAdapter must be instance of "%s" but actual instance is "%s"', StefanoDbAdapter::class, get_class($dbAdapter)
            );
        }

        return $dbAdapter;
    }

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
