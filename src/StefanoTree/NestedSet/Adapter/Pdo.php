<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\Options;

class Pdo implements AdapterInterface
{
    private $connection;
    private $options;

    /**
     * @param Options $options
     * @param \PDO    $connection
     */
    public function __construct(Options $options, \PDO $connection)
    {
        $this->connection = $connection;
        $this->options = $options;
    }

    /**
     * @return \PDO
     */
    private function getConnection(): \PDO
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
        return $this->getConnection()
                    ->inTransaction();
    }

    public function canHandleNestedTransaction(): bool
    {
        return false;
    }

    public function quoteIdentifier(string $columnName): string
    {
        return $columnName;
    }

    public function executeInsertSQL(string $sql, array $params = array())
    {
        $options = $this->getOptions();
        $this->executeSQL($sql, $params);

        if (array_key_exists($options->getIdColumnName(), $params)) {
            return $params[$options->getIdColumnName()];
        } else {
            if ('' != $options->getSequenceName()) {
                $lastGeneratedValue = $this->getConnection()
                                           ->lastInsertId($options->getSequenceName());
            } else {
                $lastGeneratedValue = $this->getConnection()
                                           ->lastInsertId();
            }

            return $lastGeneratedValue;
        }
    }

    public function executeSQL(string $sql, array $params = array()): void
    {
        $stm = $this->getConnection()
                    ->prepare($sql);
        $stm->execute($params);
    }

    public function executeSelectSQL(string $sql, array $params = array()): array
    {
        $stm = $this->getConnection()
                    ->prepare($sql);
        $stm->execute($params);

        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }
}
