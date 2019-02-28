<?php

declare(strict_types=1);

namespace StefanoTree;

use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet\QueryBuilder\AncestorQueryBuilderInterface;
use StefanoTree\NestedSet\QueryBuilder\DescendantQueryBuilderInterface;

interface TreeInterface
{
    const PLACEMENT_TOP = 'top';
    const PLACEMENT_BOTTOM = 'bottom';
    const PLACEMENT_CHILD_TOP = 'childTop';
    const PLACEMENT_CHILD_BOTTOM = 'childBottom';

    /**
     * Create root node.
     *
     * @param array           $data
     * @param string|int|null $scope Required if scope is used
     *
     * @throws ValidationException if root already exist
     *
     * @return int|string Id of new created root
     */
    public function createRootNode($data = array(), $scope = null);

    /**
     * Get root note.
     *
     * @param string|int|null $scope Required if scope is used
     *
     * @return array
     */
    public function getRootNode($scope = null): array;

    /**
     * Get root nodes.
     *
     * @return array
     */
    public function getRoots(): array;

    /**
     * Update node.
     *
     * @param int|string $nodeId
     * @param array      $data
     */
    public function updateNode($nodeId, array $data): void;

    /**
     * @param int|string $targetNodeId
     * @param array      $data
     * @param string     $placement
     *
     * @throws ValidationException if node was not created
     *
     * @return int|string id of new created node
     */
    public function addNode($targetNodeId, array $data = array(), string $placement = self::PLACEMENT_CHILD_TOP);

    /**
     * @param int    $sourceNodeId
     * @param int    $targetNodeId
     * @param string $placement
     *
     * @throws ValidationException if node was not moved
     */
    public function moveNode($sourceNodeId, $targetNodeId, string $placement = self::PLACEMENT_CHILD_TOP): void;

    /**
     * Delete node with nodeId and all its descendants.
     *
     * @param int|string $nodeId
     */
    public function deleteBranch($nodeId): void;

    /**
     * Return node.
     *
     * @param int|string $nodeId
     *
     * @return array|null
     */
    public function getNode($nodeId): ?array;

    /**
     * @return AncestorQueryBuilderInterface
     */
    public function getAncestorsQueryBuilder(): AncestorQueryBuilderInterface;

    /**
     * @return DescendantQueryBuilderInterface
     */
    public function getDescendantsQueryBuilder(): DescendantQueryBuilderInterface;

    /**
     * Check if left index, right index, level is in consistent state.
     *
     * @param int|string $rootNodeId
     *
     * @throws ValidationException if cannot validate tree
     *
     * @return bool
     */
    public function isValid($rootNodeId): bool;

    /**
     * Repair broken tree.
     * Works only if [id, parent_id] pair is not broken.
     *
     * @param int|string $rootNodeId
     *
     * @throws ValidationException if cannot rebuilt tree
     */
    public function rebuild($rootNodeId): void;
}
