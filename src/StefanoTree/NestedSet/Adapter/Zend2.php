<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\Options;
use Zend\Db\Adapter\Adapter as DbAdapter;

class Zend2 implements AdapterInterface
{
    private $connection;
    private $options;

    /**
     * @param Options   $options
     * @param DbAdapter $connection
     */
    public function __construct(Options $options, DbAdapter $connection)
    {
        $this->connection = $connection;
        $this->options = $options;
    }

    /**
     * @return DbAdapter
     */
    private function getConnection(): DbAdapter
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
        return $this->getConnection()
            ->getDriver()
            ->getConnection()
            ->inTransaction();
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

    public function executeInsertSQL(string $sql, array $params = array())
    {
        $options = $this->getOptions();
        $this->executeSQL($sql, $params);

        if (array_key_exists($options->getIdColumnName(), $params)) {
            return $params[$options->getIdColumnName()];
        } else {
            $lastGeneratedValue = $this->getConnection()
                ->getDriver()
                ->getLastGeneratedValue($options->getSequenceName());

            return $lastGeneratedValue;
        }
    }

    public function executeSQL(string $sql, array $params = array()): void
    {
        $this->getConnection()
            ->query($sql, $params);
    }

    public function executeSelectSQL(string $sql, array $params = array()): array
    {
        return $this->getConnection()
            ->query($sql, $params)
            ->toArray();
    }
}
