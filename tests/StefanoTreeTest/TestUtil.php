<?php
namespace StefanoTreeTest;

use Doctrine\DBAL;
use PDO;
use StefanoDb\Adapter\Adapter as StefanoDbAdapter;
use Zend\Db\Adapter\Adapter as Zend2DbAdapter;

class TestUtil
{
    private static $dbConnection;
    private static $zend2DbAdapter;
    private static $zend1DbAdapter;
    private static $stefanoDbAdapter;
    private static $doctrine2Connection;

    public static function createDbScheme()
    {
        $connection = self::getPDOConnection();

        $queries = array();

        if ('mysql' == TEST_STEFANO_DB_ADAPTER) {
            $queries[] = 'DROP TABLE IF EXISTS `tree_traversal`';
            $queries[] = 'DROP TABLE IF EXISTS `tree_traversal_with_scope`';

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

            $queries[] = 'ALTER TABLE `tree_traversal`
                ADD FOREIGN KEY (`parent_id`) 
                REFERENCES `tree_traversal` (`tree_traversal_id`) 
                ON DELETE CASCADE ON UPDATE CASCADE';

            $queries[] =  'CREATE TABLE `tree_traversal_with_scope` (
                `tree_traversal_id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                `lft` int(11) NOT NULL,
                `rgt` int(11) NOT NULL,
                `parent_id` int(11) DEFAULT NULL,
                `level` int(11) DEFAULT NULL,
                `scope` int(11) NOT NULL,
                PRIMARY KEY (`tree_traversal_id`),
                KEY `parent_id` (`parent_id`),
                KEY `level` (`level`),
                KEY `lft` (`lft`),
                KEY `rgt` (`rgt`),
                KEY `scope` (`scope`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin';

            $queries[] = 'ALTER TABLE `tree_traversal_with_scope`
                ADD FOREIGN KEY (`parent_id`) 
                REFERENCES `tree_traversal_with_scope` (`tree_traversal_id`) 
                ON DELETE CASCADE ON UPDATE CASCADE';
        } elseif ('pgsql' == TEST_STEFANO_DB_ADAPTER) {
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal';
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal_with_scope';

            $queries[] = 'CREATE TABLE tree_traversal (
                  tree_traversal_id serial NOT NULL,
                  name character varying(255),
                  lft integer NOT NULL,
                  rgt integer NOT NULL,
                  parent_id integer,
                  level integer,
                  CONSTRAINT tree_traversal_pkey PRIMARY KEY (tree_traversal_id),
                  CONSTRAINT tree_traversal_parent_id_fkey FOREIGN KEY (parent_id)
                    REFERENCES public.tree_traversal (tree_traversal_id) MATCH SIMPLE
                    ON UPDATE CASCADE ON DELETE CASCADE
                )';

            $queries[] = 'CREATE INDEX tree_traversal_level
                ON public.tree_traversal
                USING btree (level)';

            $queries[] = 'CREATE INDEX tree_traversal_lft
                ON public.tree_traversal
                USING btree (lft)';

            $queries[] = 'CREATE INDEX tree_traversal_parent_id
                ON public.tree_traversal
                USING btree (parent_id)';

            $queries[] = 'CREATE INDEX tree_traversal_rgt
                  ON public.tree_traversal
                  USING btree (rgt)';

            $queries[] = 'CREATE TABLE tree_traversal_with_scope (
                  tree_traversal_id serial NOT NULL,
                  name character varying(255),
                  lft integer NOT NULL,
                  rgt integer NOT NULL,
                  parent_id integer,
                  level integer,
                  scope integer NOT NULL,
                  CONSTRAINT tree_traversal_with_scope_pkey PRIMARY KEY (tree_traversal_id),
                  CONSTRAINT tree_traversal_with_scope_parent_id_fkey FOREIGN KEY (parent_id)
                    REFERENCES public.tree_traversal_with_scope (tree_traversal_id) MATCH SIMPLE
                    ON UPDATE CASCADE ON DELETE CASCADE
                )';

            $queries[] = 'CREATE INDEX tree_traversal_with_scope_level
                  ON public.tree_traversal_with_scope
                  USING btree (level)';

            $queries[] = 'CREATE INDEX tree_traversal_with_scope_lft
                  ON public.tree_traversal_with_scope
                  USING btree (lft)';

            $queries[] = 'CREATE INDEX tree_traversal_with_scope_parent_id
                  ON public.tree_traversal_with_scope
                  USING btree (parent_id)';

            $queries[] = 'CREATE INDEX tree_traversal_with_scope_rgt
                  ON public.tree_traversal_with_scope
                  USING btree (rgt)';

            $queries[] = 'CREATE INDEX tree_traversal_with_scope_scope
                  ON public.tree_traversal_with_scope
                  USING btree (scope)';
        } else {
            throw new \Exception(sprintf('Unsupported vendor %s', TEST_STEFANO_DB_ADAPTER));
        }

        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * Singleton
     * @return PDO
     */
    public static function getPDOConnection()
    {
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

    /**
     * Singleton
     * @return Zend2DbAdapter
     */
    public static function getZend2DbAdapter()
    {
        if (null == self::$zend2DbAdapter) {
            self::$zend2DbAdapter = new Zend2DbAdapter(array(
                'driver' => 'Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER),
                'hostname' => TEST_STEFANO_DB_HOSTNAME,
                'database' => TEST_STEFANO_DB_DB_NAME,
                'username' => TEST_STEFANO_DB_USER,
                'password' => TEST_STEFANO_DB_PASSWORD
            ));
        }
        return self::$zend2DbAdapter;
    }

    /**
     * Singleton
     * @return \Zend_Db_Adapter_Abstract
     */
    public static function getZend1DbAdapter()
    {
        if (null == self::$zend1DbAdapter) {
            self::$zend1DbAdapter = \Zend_Db::factory('Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER), array(
                'host' => TEST_STEFANO_DB_HOSTNAME,
                'dbname' => TEST_STEFANO_DB_DB_NAME,
                'username' => TEST_STEFANO_DB_USER,
                'password' => TEST_STEFANO_DB_PASSWORD
            ));
        }
        return self::$zend1DbAdapter;
    }

    /**
     * Singleton
     * @return StefanoDbAdapter
     */
    public static function getStefanoDbAdapter()
    {
        if (null == self::$stefanoDbAdapter) {
            self::$stefanoDbAdapter = new StefanoDbAdapter(array(
                'driver' => 'Pdo_' . ucfirst(TEST_STEFANO_DB_ADAPTER),
                'hostname' => TEST_STEFANO_DB_HOSTNAME,
                'database' => TEST_STEFANO_DB_DB_NAME,
                'username' => TEST_STEFANO_DB_USER,
                'password' => TEST_STEFANO_DB_PASSWORD
            ));
        }
        return self::$stefanoDbAdapter;
    }

    /**
     * Singleton
     * @return DBAL\Connection
     */
    public static function getDoctrine2Connection()
    {
        if (null == self::$doctrine2Connection) {
            $config = new DBAL\Configuration();
            $connectionParams = array(
                'dbname' => TEST_STEFANO_DB_DB_NAME,
                'user' => TEST_STEFANO_DB_USER,
                'password' => TEST_STEFANO_DB_PASSWORD,
                'host' => TEST_STEFANO_DB_HOSTNAME,
                'driver' => 'pdo_' . strtolower(TEST_STEFANO_DB_ADAPTER),
            );

            self::$doctrine2Connection = DBAL\DriverManager::getConnection($connectionParams, $config);
        }
        return self::$doctrine2Connection;
    }
}
