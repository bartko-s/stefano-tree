<?php

declare(strict_types=1);

namespace StefanoTreeTest\DbTester;

use PDO;

trait DbTestCaseTrait
{
    /**
     * @var null|Connection
     */
    private $connection = null;

    /**
     * Performs operation returned by getSetUpOperation().
     */
    protected function setUp(): void
    {
        $this->recreateDbScheme();
        $this->getConnection()
            ->insertInitData($this->getDataSet());
        parent::setUp();
    }

    /**
     * Performs operation returned by getTearDownOperation().
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->connection = null;
    }

    abstract public function recreateDbScheme();

    public function assertCompareDataSet(array $tables, $expectedDataSetArrayFile, $message = '')
    {
        $currentDataSet = $this->getConnection()
            ->createDataSourceFromCurrentDatabaseState($tables);
        $expectedDataSet = $this->createArrayDataSet(include $expectedDataSetArrayFile);

        $actualData = array();
        $expectedData = array();
        foreach ($tables as $table) {
            $expectedData[$table] = $expectedDataSet->getTableData($table, true);
            $actualData[$table] = $currentDataSet->getTableData($table, true);
        }
        // todo better validation with better, readable error result
        self::assertEquals($expectedData, $actualData, $message);
    }

    /**
     * Returns fresh test database connection.
     *
     * @return PDO
     */
    abstract protected function getPdoConnection();

    private function getConnection(): Connection
    {
        if (null == $this->connection) {
            $this->connection = new Connection($this->getPdoConnection());
        }

        return $this->connection;
    }

    /**
     * Returns the initial test dataset.
     *
     * @return ArrayDataSource
     */
    abstract protected function getDataSet();

    /**
     * @param array $data
     *
     * @return ArrayDataSource
     */
    protected function createArrayDataSet(array $data)
    {
        return new ArrayDataSource($data);
    }
}
