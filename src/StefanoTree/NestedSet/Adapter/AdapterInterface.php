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
     *
     * @return bool
     */
    public function isInTransaction(): bool;

    /**
     * Return true if adapter can handle nested transaction.
     *
     * @return bool
     */
    public function canHandleNestedTransaction(): bool;

    /**
     * Quote column identifier so it is safe to use, even it is a reserved world.
     *
     * @param string $columnName
     *
     * @return string
     */
    public function quoteIdentifier(string $columnName): string;

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return int|string Last ID
     */
    public function executeInsertSQL(string $sql, array $params = array());

    /**
     * @param string $sql
     * @param array  $params
     */
    public function executeSQL(string $sql, array $params = array()): void;

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return array
     */
    public function executeSelectSQL(string $sql, array $params = array()): array;
}
