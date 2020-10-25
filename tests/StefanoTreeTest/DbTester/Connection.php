<?php

declare(strict_types=1);

namespace StefanoTreeTest\DbTester;

use PDO;

class Connection
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }

    public function insertInitData(ArrayDataSource $dataSource)
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

    public function createDataSourceFromCurrentDatabaseState(array $tables): ArrayDataSource
    {
        $connection = $this->pdo;

        $data = array();
        foreach ($tables as $tableName) {
            $sql = sprintf(
                'SELECT * FROM %s',
                $this->quoteIdentifier($tableName)
            );

            $rows = $connection->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);
            $data[$tableName] = $rows;
        }

        return new ArrayDataSource($data);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return $identifier; // todo Quote identifier. Possible SQL injection
    }
}
