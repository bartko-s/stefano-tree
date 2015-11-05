<?php
namespace StefanoTreeTest;

use \PDO;

class TestUtil
{
    private static $dbConnection;

    public static function createDbScheme() {
        $sql =  'CREATE TABLE IF NOT EXISTS `tree_traversal` (
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

        self::getPDOConnection()
            ->query($sql);
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
