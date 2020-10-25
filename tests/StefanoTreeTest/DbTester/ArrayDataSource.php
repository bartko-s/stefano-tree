<?php

declare(strict_types=1);

namespace StefanoTreeTest\DbTester;

class ArrayDataSource
{
    private $dataSource;

    /**
     * array(
     *      tableName => (
     *          array(
     *              columnName => value, anotherColumnName => value
     *          ),
     *          another row
     *      ),
     *      another table
     * ).
     *
     * @param array $dataSource
     */
    public function __construct(array $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function getTableNames(): array
    {
        $tables = array();
        foreach ($this->dataSource as $tableName => $_) {
            $tables[] = $tableName;
        }

        return $tables;
    }

    public function getTableData(string $tableName, bool $sort = false)
    {
        if (array_key_exists($tableName, $this->dataSource)) {
            if ($sort) {
                return $this->sortData($this->dataSource[$tableName]);
            } else {
                return $this->dataSource[$tableName];
            }
        } else {
            throw new \Exception(sprintf(
                'Table "%s" does not exists',
                $tableName
            ));
        }
    }

    private function sortData(array $data): array
    {
        usort($data, function ($a, $b) {
            reset($a);
            reset($b);

            $a = $a[key($a)];
            $b = $b[key($b)];

            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return $data;
    }
}
