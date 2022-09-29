<?php

declare(strict_types=1);

namespace StefanoTreeTest;

use Doctrine\DBAL;
use Laminas\Db\Adapter\Adapter as LaminasDbAdapter;
use PDO;
use StefanoTree\NestedSet;
use StefanoTree\NestedSet\Adapter;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Options;

class TestUtil
{
    private static $dbConnection;
    private static $laminasDbAdapter;
    private static $zend1DbAdapter;
    private static $doctrine2Connection;

    public static function createDbScheme()
    {
        $connection = self::getPDOConnection();

        $queries = array();

        if ('mysql' == TEST_STEFANO_DB_VENDOR) {
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal';
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal_with_scope';
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal_metadata';

            $queries[] = 'CREATE TABLE `tree_traversal` (
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

            $queries[] = 'CREATE TABLE `tree_traversal_with_scope` (
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

            $queries[] = 'CREATE TABLE `tree_traversal_metadata` (
                `tree_traversal_metadata_id` int(11) NOT NULL AUTO_INCREMENT,
                `tree_traversal_id` int(11) NOT NULL,
                `lft` int(11) DEFAULT NULL,
                `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                PRIMARY KEY (`tree_traversal_metadata_id`),
                KEY `tree_traversal_id` (`tree_traversal_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin';

            $queries[] = 'ALTER TABLE `tree_traversal_with_scope`
                ADD FOREIGN KEY (`parent_id`) 
                REFERENCES `tree_traversal_with_scope` (`tree_traversal_id`) 
                ON DELETE CASCADE ON UPDATE CASCADE';
        } elseif ('pgsql' == TEST_STEFANO_DB_VENDOR) {
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal';
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal_with_scope';
            $queries[] = 'DROP TABLE IF EXISTS tree_traversal_metadata';

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

            $queries[] = 'CREATE TABLE tree_traversal_metadata (
                tree_traversal_metadata_id serial NOT NULL,
                tree_traversal_id integer NOT NULL,
                lft integer,
                name character varying(255),
                CONSTRAINT tree_traversal_metadata_pkey PRIMARY KEY (tree_traversal_metadata_id)
            )';

            $queries[] = 'CREATE INDEX tree_traversal_metadata_tree_traversal_id
                  ON public.tree_traversal_metadata
                  USING btree (tree_traversal_id)';
        } else {
            throw new \Exception(sprintf('Unsupported vendor %s', TEST_STEFANO_DB_VENDOR));
        }

        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * Singleton.
     *
     * @return PDO
     */
    public static function getPDOConnection()
    {
        if (null == self::$dbConnection) {
            $adapter = strtolower(TEST_STEFANO_DB_VENDOR);
            $hostname = TEST_STEFANO_DB_HOSTNAME;
            $dbName = TEST_STEFANO_DB_DB_NAME;
            $user = TEST_STEFANO_DB_USER;
            $password = TEST_STEFANO_DB_PASSWORD;

            self::$dbConnection = new PDO(
                $adapter.':host='.$hostname.';dbname='
                .$dbName,
                $user,
                $password
            );
        }

        return self::$dbConnection;
    }

    /**
     * Singleton.
     *
     * @return LaminasDbAdapter
     */
    public static function getLaminasDbAdapter()
    {
        if (null == self::$laminasDbAdapter) {
            self::$laminasDbAdapter = new LaminasDbAdapter(array(
                'driver' => 'Pdo_'.ucfirst(TEST_STEFANO_DB_VENDOR),
                'hostname' => TEST_STEFANO_DB_HOSTNAME,
                'database' => TEST_STEFANO_DB_DB_NAME,
                'username' => TEST_STEFANO_DB_USER,
                'password' => TEST_STEFANO_DB_PASSWORD,
            ));
        }

        return self::$laminasDbAdapter;
    }

    /**
     * Singleton.
     *
     * @return \Zend_Db_Adapter_Abstract
     */
    public static function getZend1DbAdapter()
    {
        if (null == self::$zend1DbAdapter) {
            self::$zend1DbAdapter = \Zend_Db::factory('Pdo_'.ucfirst(TEST_STEFANO_DB_VENDOR), array(
                'host' => TEST_STEFANO_DB_HOSTNAME,
                'dbname' => TEST_STEFANO_DB_DB_NAME,
                'username' => TEST_STEFANO_DB_USER,
                'password' => TEST_STEFANO_DB_PASSWORD,
            ));
        }

        return self::$zend1DbAdapter;
    }

    /**
     * Singleton.
     *
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
                'driver' => 'pdo_'.strtolower(TEST_STEFANO_DB_VENDOR),
            );

            self::$doctrine2Connection = DBAL\DriverManager::getConnection($connectionParams, $config);
        }

        return self::$doctrine2Connection;
    }

    /**
     * Build Adapter based on ENV variable TEST_STEFANO_ADAPTER.
     *
     * @param Options $options
     *
     * @return AdapterInterface
     */
    public static function buildAdapter(Options $options): NestedSet\Adapter\AdapterInterface
    {
        switch (TEST_STEFANO_ADAPTER) {
            case 'pdo':
                $adapter = new Adapter\Pdo($options, self::getPDOConnection());

                break;

            case 'zend1':
                $adapter = new Adapter\Zend1($options, self::getZend1DbAdapter());

                break;

            case 'laminas-db':
                $adapter = new Adapter\LaminasDb($options, self::getLaminasDbAdapter());

                break;

            case 'doctrine2-dbal':
                $adapter = new Adapter\Doctrine2DBAL($options, self::getDoctrine2Connection());

                break;

            default:
                throw new \Exception(sprintf('Unknown adapter "%s"', TEST_STEFANO_ADAPTER));
        }

        return $adapter;
    }
}
