<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use Doctrine\DBAL\Connection as DbConnection;
use StefanoTree\NestedSet\Options;

class Doctrine2DBAL implements AdapterInterface
{
    private $connection;
    private $options;

    /**
     * @param Options      $options
     * @param DbConnection $connection
     */
    public function __construct(Options $options, DbConnection $connection)
    {
        $this->connection = $connection;
        $this->options = $options;
    }

    /**
     * @return DbConnection
     */
    private function getConnection(): DbConnection
    {
        return $this->connection;
    }

    /**
     * @return Options
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    public function beginTransaction(): void
    {
        $this->getConnection()
            ->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->getConnection()
            ->commit();
    }

    public function rollbackTransaction(): void
    {
        $this->getConnection()
            ->rollBack();
    }

    public function isInTransaction(): bool
    {
        return 0 === $this->getConnection()->getTransactionNestingLevel() ? false : true;
    }

    public function canHandleNestedTransaction(): bool
    {
        return true;
    }

    public function quoteIdentifier(string $columnName): string
    {
        return $this->getConnection()
            ->quoteIdentifier($columnName);
    }

    public function executeInsertSQL(string $sql, array $params = array())
    {
        $options = $this->getOptions();
        $this->executeSQL($sql, $params);

        if (array_key_exists($options->getIdColumnName(), $params)) {
            return $params[$options->getIdColumnName()];
        } else {
            return $this->getConnection()
                ->lastInsertId($options->getSequenceName());
        }
    }

    public function executeSQL(string $sql, array $params = array()): void
    {
        $this->getConnection()
            ->executeQuery($sql, $params);
    }

    public function executeSelectSQL(string $sql, array $params = array()): array
    {
        return $this->getConnection()
            ->executeQuery($sql, $params)
            ->fetchAll();
    }
}
