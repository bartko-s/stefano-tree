<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet\Adapter;

class NestedTransactionDecorator implements AdapterInterface
{
    private $counter = 0;
    private $rollbackOnly = false;
    private $transactionWasOpenOutside = false;

    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function beginTransaction(): void
    {
        $a = $this->getAdapter();

        if ($a->canHandleNestedTransaction()) {
            $a->beginTransaction();
        } else {
            if (0 === $this->counter && $a->isInTransaction()) {
                $this->transactionWasOpenOutside = true;
            }

            if (0 === $this->counter && false === $this->transactionWasOpenOutside) {
                $a->beginTransaction();
                $this->counter = $this->counter + 1;
            } else {
                $this->counter = $this->counter + 1;
            }
        }
    }

    public function commitTransaction(): void
    {
        $a = $this->getAdapter();

        if ($a->canHandleNestedTransaction()) {
            $a->commitTransaction();
        } else {
            if ($this->rollbackOnly) {
                throw new \Exception('Cannot commit Transaction was marked as rollback only');
            }

            if (1 === $this->counter) {
                if (false === $this->transactionWasOpenOutside) {
                    $a->commitTransaction();
                } else {
                    $this->transactionWasOpenOutside = false;
                }
                $this->counter = 0;
            } else {
                $this->counter = $this->counter - 1;
            }
        }
    }

    public function rollbackTransaction(): void
    {
        $a = $this->getAdapter();

        if ($a->canHandleNestedTransaction()) {
            $a->rollbackTransaction();
        } else {
            if (1 === $this->counter) {
                if (false === $this->transactionWasOpenOutside) {
                    $a->rollbackTransaction();
                }
                $this->counter = 0;
                $this->rollbackOnly = false;
                $this->transactionWasOpenOutside = false;
            } else {
                $this->counter = $this->counter - 1;
            }

            if ($this->counter > 0) {
                $this->rollbackOnly = true;
            }
        }
    }

    public function isInTransaction(): bool
    {
        return $this->getAdapter()
            ->isInTransaction();
    }

    public function canHandleNestedTransaction(): bool
    {
        return true;
    }

    public function quoteIdentifier(string $columnName): string
    {
        return $this->getAdapter()
            ->quoteIdentifier($columnName);
    }

    public function executeInsertSQL(string $sql, array $params = array())
    {
        return $this->getAdapter()
            ->executeInsertSQL($sql, $params);
    }

    public function executeSQL(string $sql, array $params = array()): void
    {
        $this->getAdapter()
            ->executeSQL($sql, $params);
    }

    public function executeSelectSQL(string $sql, array $params = array()): array
    {
        return $this->getAdapter()
            ->executeSelectSQL($sql, $params);
    }
}
