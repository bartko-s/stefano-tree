<?php

namespace StefanoTree\NestedSet\Validator;

interface ValidatorInterface
{
    /**
     * Check if tree indexes, levels is not corrupted.
     *
     * @param $rootNodeId int
     *
     * @return bool
     */
    public function isValid($rootNodeId);

    /**
     * Rebuild broken tree left indexes, right indexes, levels.
     *
     * @param $rootNodeId int
     */
    public function rebuild($rootNodeId);
}
