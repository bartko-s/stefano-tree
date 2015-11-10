<?php
namespace StefanoTreeTest;

use \PDO;

class TestUtil
{
    private static $dbConnection;

    public static function createDbScheme() {
        $connection = self::getPDOConnection();

        $queries = array();

        if('mysql' == TEST_STEFANO_DB_ADAPTER) {
            $queries[] = 'DROP TABLE IF EXISTS `tree_traversal`';

            $queries[] =  'CREATE TABLE `tree_traversal` (
                `tree_traversal_id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                `lft` int(11) NOT NULL,
                `rgt` int(11) NOT NULL,
                `parent_id` int(11) DEFAULT NULL,
                `level` int(11) DEFAULT NULL,
                PRIMARY KEY (`tree_traversal_id`),
                KEY `parent_id` (`parent_id`),
                KEY `level` (`level`),
                KEY `lft` (`lft`),
                KEY `rgt` (`rgt`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin';
        } elseif('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal';

            $queries[] = 'CREATE TABLE tree_traversal (
                  tree_traversal_id serial NOT NULL,
                  name character varying(255),
                  lft integer NOT NULL,
                  rgt integer NOT NULL,
                  parent_id integer,
                  level integer,
                  CONSTRAINT tree_traversal_pkey PRIMARY KEY (tree_traversal_id)
                )';
        } else {
            throw new \Exception(sprintf('Unsupported vendor %s', TEST_STEFANO_DB_ADAPTER));
        }

        foreach($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * Singleton
     * @return PDO
     */
    public static function getPDOConnection() {
        if (null == self::$dbConnection) {
            $adapter    = strtolower(TEST_STEFANO_DB_ADAPTER);
            $hostname   = TEST_STEFANO_DB_HOSTNAME;
            $dbName     = TEST_STEFANO_DB_DB_NAME;
            $user       = TEST_STEFANO_DB_USER;
            $password   = TEST_STEFANO_DB_PASSWORD;

            self::$dbConnection = new PDO(
                $adapter . ':host=' . $hostname . ';dbname='
                . $dbName, $user, $password
            );
        }
        return self::$dbConnection;
    }
}
