<?php

declare(strict_types=1);

namespace StefanoTreeTest\DbTester;

class ArrayDataSource
{
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
     * @param array<string, list<array<string, mixed>>> $dataSource
     */
    public function __construct(
        private readonly array $dataSource,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getTableNames(): array
    {
        $tables = array();
        foreach ($this->dataSource as $tableName => $_) {
            $tables[] = $tableName;
        }

        return $tables;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTableData(string $tableName, bool $sort = false): array
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

    /**
     * @param list<array<string, mixed>> $data
     *
     * @return list<array<string, mixed>>
     */
    private function sortData(array $data): array
    {
        usort($data, function (array $rowA, array $rowB): int {
            reset($rowA);
            reset($rowB);

            $keyA = key($rowA);
            $keyB = key($rowB);
            \assert(null !== $keyA && null !== $keyB);

            $valueA = $rowA[$keyA];
            $valueB = $rowB[$keyB];

            if ($valueA == $valueB) {
                return 0;
            }

            return ($valueA < $valueB) ? -1 : 1;
        });

        return $data;
    }
}
