<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Adapter\DoctrineDBAL;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;

/**
 * @internal
 */
class DoctrineDBALTest extends AdapterAbstract
{
    /**
     * @return AdapterInterface
     */
    protected function getAdapter(): AdapterInterface
    {
        if (null === $this->adapter) {
            $options = new Options(array(
                'tableName' => 'tree_traversal',
                'idColumnName' => 'tree_traversal_id',
            ));

            $this->adapter = new DoctrineDBAL($options, TestUtil::getDoctrineDBALConnection());
        }

        return $this->adapter;
    }
}
