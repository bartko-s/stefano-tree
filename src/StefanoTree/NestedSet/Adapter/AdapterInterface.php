<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

interface AdapterInterface
{
    /**
     * Begin db transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commit db transaction.
     */
    public function commitTransaction(): void;

    /**
     * Rollback db transaction.
     */
    public function rollbackTransaction(): void;

    /**
     * Check if Db transaction is active.
     */
    public function isInTransaction(): bool;

    /**
     * Return true if adapter can handle nested transaction.
     */
    public function canHandleNestedTransaction(): bool;

    /**
     * Quote column identifier so it is safe to use, even it is a reserved world.
     */
    public function quoteIdentifier(string $columnName): string;

    /**
     * @param string               $sql
     * @param array<string, mixed> $params
     *
     * @return int|string Last ID
     */
    public function executeInsertSQL(string $sql, array $params = array()): int|string;

    /**
     * @param string               $sql
     * @param array<string, mixed> $params
     */
    public function executeSQL(string $sql, array $params = array()): void;

    /**
     * @param string               $sql
     * @param array<string, mixed> $params
     *
     * @return array<int, array<string, mixed>>
     */
    public function executeSelectSQL(string $sql, array $params = array()): array;
}
