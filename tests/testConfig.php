<?php
/**
 * DB connection settings
 */
defined('TEST_STEFANO_DB_ADAPTER') 
    || define('TEST_STEFANO_DB_ADAPTER', 'mysql');
defined('TEST_STEFANO_DB_HOSTNAME')
    || define('TEST_STEFANO_DB_HOSTNAME', '127.0.0.1');
defined('TEST_STEFANO_DB_DB_NAME')
    || define('TEST_STEFANO_DB_DB_NAME', 'stefano_tests');
defined('TEST_STEFANO_DB_USER')
    || define('TEST_STEFANO_DB_USER', 'root');
defined('TEST_STEFANO_DB_PASSWORD')
    || define('TEST_STEFANO_DB_PASSWORD', 'test');