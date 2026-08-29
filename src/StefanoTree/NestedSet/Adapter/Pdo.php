<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\Options;

class Pdo implements AdapterInterface
{
    public function __construct(
        private readonly Options $options,
        private readonly \PDO $connection,
    ) {
    }

    private function getConnection(): \PDO
    {
        return $this->connection;
    }

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

    public function executeInsertSQL(string $sql, array $params = array()): int|string
    {
        $options = $this->getOptions();

        if (array_key_exists($options->getIdColumnName(), $params)) {
            $this->executeSQL($sql, $params);

            return $params[$options->getIdColumnName()];
        }

        $stm = $this->getConnection()
            ->prepare($sql.' RETURNING '.$this->quoteIdentifier($options->getIdColumnName()));
        $stm->execute($params);

        $row = $stm->fetch(\PDO::FETCH_ASSOC);
        $lastGeneratedValue = $row[$options->getIdColumnName()] ?? null;

        if (null === $lastGeneratedValue) {
            throw new \RuntimeException(sprintf(
                'Insert did not return generated id. SQL: "%s"',
                $sql
            ));
        }

        return $lastGeneratedValue;
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
