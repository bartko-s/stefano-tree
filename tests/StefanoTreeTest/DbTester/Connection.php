<?php

declare(strict_types=1);

namespace StefanoTreeTest\DbTester;

class Connection
{
    public function __construct(private readonly \PDO $pdo)
    {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
    }

    public function insertInitData(ArrayDataSource $dataSource): void
    {
        $connection = $this->pdo;
        foreach ($dataSource->getTableNames() as $tableName) {
            foreach ($dataSource->getTableData($tableName) as $rowData) {
                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    $this->quoteIdentifier($tableName),
                    implode(', ', array_map(function ($key) {
                        return $this->quoteIdentifier($key);
                    }, array_keys($rowData))),
                    implode(', ', array_map(function ($valueKey) {
                        return ':'.$valueKey;
                    }, array_keys($rowData)))
                );

                $connection->prepare($sql)
                    ->execute($rowData);
            }
        }
    }

    /**
     * @param list<string> $tables
     */
    public function createDataSourceFromCurrentDatabaseState(array $tables): ArrayDataSource
    {
        $connection = $this->pdo;

        /**
         * @var array<string, list<array<string, mixed>>> $data
         */
        $data = array();
        foreach ($tables as $tableName) {
            $sql = sprintf(
                'SELECT * FROM %s',
                $this->quoteIdentifier($tableName)
            );

            $statement = $connection->query($sql);
            if (!$statement instanceof \PDOStatement) {
                throw new \RuntimeException(sprintf(
                    'Query failed. SQL: "%s"',
                    $sql
                ));
            }

            /**
             * @var list<array<string, mixed>> $rows
             */
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $data[$tableName] = $rows;
        }

        return new ArrayDataSource($data);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return $identifier; // todo Quote identifier. Possible SQL injection
    }
}
