<?php

declare(strict_types=1);

namespace StefanoTree;

use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet\QueryBuilder\AncestorQueryBuilderInterface;
use StefanoTree\NestedSet\QueryBuilder\DescendantQueryBuilderInterface;

interface TreeInterface
{
    public const PLACEMENT_TOP = 'top';
    public const PLACEMENT_BOTTOM = 'bottom';
    public const PLACEMENT_CHILD_TOP = 'childTop';
    public const PLACEMENT_CHILD_BOTTOM = 'childBottom';

    /**
     * Create root node.
     *
     * @param array<string, mixed> $data
     * @param null|int|string      $scope Required if scope is used
     *
     * @return int|string Id of new created root
     *
     * @throws ValidationException if root already exist
     */
    public function createRootNode(array $data = array(), int|string|null $scope = null): int|string;

    /**
     * Get root note.
     *
     * @param null|int|string $scope Required if scope is used
     *
     * @return array<string, mixed>
     */
    public function getRootNode(int|string|null $scope = null): array;

    /**
     * Get root nodes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoots(): array;

    /**
     * Update node.
     *
     * @param int|string           $nodeId
     * @param array<string, mixed> $data
     */
    public function updateNode(int|string $nodeId, array $data): void;

    /**
     * @param int|string           $targetNodeId
     * @param array<string, mixed> $data
     * @param string               $placement
     *
     * @return int|string id of new created node
     *
     * @throws ValidationException if node was not created
     */
    public function addNode(int|string $targetNodeId, array $data = array(), string $placement = self::PLACEMENT_CHILD_TOP): int|string;

    /**
     * @param int    $sourceNodeId
     * @param int    $targetNodeId
     * @param string $placement
     *
     * @throws ValidationException if node was not moved
     */
    public function moveNode(int|string $sourceNodeId, int|string $targetNodeId, string $placement = self::PLACEMENT_CHILD_TOP): void;

    /**
     * Delete node with nodeId and all its descendants.
     *
     * @param int|string $nodeId
     */
    public function deleteBranch(int|string $nodeId): void;

    /**
     * Return node.
     *
     * @param int|string $nodeId
     *
     * @return null|array<string, mixed>
     */
    public function getNode(int|string $nodeId): ?array;

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
     * @return bool
     *
     * @throws ValidationException if cannot validate tree
     */
    public function isValid(int|string $rootNodeId): bool;

    /**
     * Repair broken tree.
     * Works only if [id, parent_id] pair is not broken.
     *
     * @param int|string $rootNodeId
     *
     * @throws ValidationException if cannot rebuilt tree
     */
    public function rebuild(int|string $rootNodeId): void;
}
