<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Adapter\Driver\AbstractConnection;
use Laminas\Db\ResultSet\ResultSet;
use StefanoTree\NestedSet\Options;

class LaminasDb implements AdapterInterface
{
    public function __construct(
        private readonly Options $options,
        private readonly DbAdapter $connection,
    ) {
    }

    private function getConnection(): DbAdapter
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
            ->getDriver()
            ->getConnection()
            ->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->getConnection()
            ->getDriver()
            ->getConnection()
            ->commit();
    }

    public function rollbackTransaction(): void
    {
        $this->getConnection()
            ->getDriver()
            ->getConnection()
            ->rollback();
    }

    public function isInTransaction(): bool
    {
        /** all shipped driver connections extend AbstractConnection */
        /** @var AbstractConnection $driverConnection */
        $driverConnection = $this->getConnection()
            ->getDriver()
            ->getConnection();

        return $driverConnection->inTransaction();
    }

    public function canHandleNestedTransaction(): bool
    {
        return true;
    }

    public function quoteIdentifier(string $columnName): string
    {
        return $this->getConnection()
            ->getPlatform()
            ->quoteIdentifierChain(explode('.', $columnName));
    }

    public function executeInsertSQL(string $sql, array $params = array()): int|string
    {
        $options = $this->getOptions();

        if (array_key_exists($options->getIdColumnName(), $params)) {
            $this->executeSQL($sql, $params);

            return $params[$options->getIdColumnName()];
        }

        $result = $this->getConnection()
            ->query(
                $sql.' RETURNING '.$this->quoteIdentifier($options->getIdColumnName()),
                $params
            );

        if (!$result instanceof ResultSet) {
            throw new \RuntimeException(sprintf(
                'Insert did not return a result set. SQL: "%s"',
                $sql
            ));
        }

        /**
         * @var list<array<string, mixed>> $rows
         */
        $rows = $result->toArray();
        $lastGeneratedValue = $rows[0][$options->getIdColumnName()] ?? null;

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
        $this->getConnection()
            ->query($sql, $params);
    }

    /**
     * @param string               $sql
     * @param array<string, mixed> $params
     *
     * @return list<array<string, mixed>>
     */
    public function executeSelectSQL(string $sql, array $params = array()): array
    {
        $result = $this->getConnection()
            ->query($sql, $params);

        if (!$result instanceof ResultSet) {
            throw new \RuntimeException(sprintf(
                'Select did not return a result set. SQL: "%s"',
                $sql
            ));
        }

        /**
         * @var list<array<string, mixed>> $rows
         */
        $rows = $result->toArray();

        return $rows;
    }
}
