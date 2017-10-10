<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;

abstract class AdapterAbstract implements AdapterInterface
{
    private $options;

    /**
     * @param Options $options
     */
    protected function setOptions(Options $options): void
    {
        $this->options = $options;
    }

    /**
     * @return Options
     */
    protected function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Data cannot contain keys like idColumnName, levelColumnName, ...
     *
     * @param array $data
     *
     * @return array
     */
    protected function cleanData(array $data): array
    {
        $options = $this->getOptions();

        $disallowedDataKeys = array(
            $options->getIdColumnName(),
            $options->getLeftColumnName(),
            $options->getRightColumnName(),
            $options->getLevelColumnName(),
            $options->getParentIdColumnName(),
        );

        if (null !== $options->getScopeColumnName()) {
            $disallowedDataKeys[] = $options->getScopeColumnName();
        }

        return array_diff_key($data, array_flip($disallowedDataKeys));
    }

    /**
     * @param array $data
     *
     * @return NodeInfo
     */
    protected function _buildNodeInfoObject(array $data)
    {
        $options = $this->getOptions();

        $id = $data[$options->getIdColumnName()];
        $parentId = $data[$options->getParentIdColumnName()];
        $level = (int) $data[$options->getLevelColumnName()];
        $left = (int) $data[$options->getLeftColumnName()];
        $right = (int) $data[$options->getRightColumnName()];

        if (isset($data[$options->getScopeColumnName()])) {
            $scope = $data[$options->getScopeColumnName()];
        } else {
            $scope = null;
        }

        return new NodeInfo($id, $parentId, $level, $left, $right, $scope);
    }
}
