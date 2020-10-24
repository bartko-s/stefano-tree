<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

use StefanoTree\NestedSet\Options;
use Zend_Db_Adapter_Abstract as ZendDbAdapter;

class Zend1 implements AdapterInterface
{
    private $connection;
    private $options;

    /**
     * @param Options       $options
     * @param ZendDbAdapter $connection
     */
    public function __construct(Options $options, ZendDbAdapter $connection)
    {
        $this->connection = $connection;
        $this->options = $options;
    }

    /**
     * @return ZendDbAdapter
     */
    private function getConnection(): ZendDbAdapter
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
            ->getConnection()
            ->inTransaction();
    }

    public function canHandleNestedTransaction(): bool
    {
        return false;
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
            if ('' != $options->getSequenceName()) {
                $lastGeneratedValue = $this->getConnection()
                    ->lastSequenceId($options->getSequenceName());
            } else {
                $lastGeneratedValue = $this->getConnection()
                    ->lastInsertId();
            }

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
            ->fetchAll();
    }
}
