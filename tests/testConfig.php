<?php

declare(strict_types=1);

if (file_exists(__DIR__.'/testConfig.local.php')) {
    include_once __DIR__.'/testConfig.local.php';
}

/*
 * DB connection settings
 */
if ('mysql' == TEST_STEFANO_DB_VENDOR) {
    defined('TEST_STEFANO_DB_HOSTNAME')
    || define('TEST_STEFANO_DB_HOSTNAME', 'mariadb');
    defined('TEST_STEFANO_DB_DB_NAME')
    || define('TEST_STEFANO_DB_DB_NAME', 'stefano_tests');
    defined('TEST_STEFANO_DB_USER')
    || define('TEST_STEFANO_DB_USER', 'root');
    defined('TEST_STEFANO_DB_PASSWORD')
    || define('TEST_STEFANO_DB_PASSWORD', '');
} elseif ('pgsql' == TEST_STEFANO_DB_VENDOR) {
    defined('TEST_STEFANO_DB_HOSTNAME')
    || define('TEST_STEFANO_DB_HOSTNAME', 'postgres');
    defined('TEST_STEFANO_DB_DB_NAME')
    || define('TEST_STEFANO_DB_DB_NAME', 'stefano_tests');
    defined('TEST_STEFANO_DB_USER')
    || define('TEST_STEFANO_DB_USER', 'root');
    defined('TEST_STEFANO_DB_PASSWORD')
    || define('TEST_STEFANO_DB_PASSWORD', '');
} else {
    throw new \Exception(sprintf('Unsupported adapter "%s"', TEST_STEFANO_DB_VENDOR));
}
