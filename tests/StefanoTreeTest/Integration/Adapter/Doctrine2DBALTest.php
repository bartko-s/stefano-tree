<?php

declare(strict_types=1);

namespace StefanoTreeTest\Integration\Adapter;

use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Adapter\Doctrine2DBAL;
use StefanoTree\NestedSet\Options;
use StefanoTreeTest\TestUtil;

class Doctrine2DBALTest extends AdapterAbstract
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

            if ('pgsql' == TEST_STEFANO_DB_VENDOR) {
                $options->setSequenceName('tree_traversal_tree_traversal_id_seq');
            }

            $this->adapter = new Doctrine2DBAL($options, TestUtil::getDoctrine2Connection());
        }

        return $this->adapter;
    }
}
