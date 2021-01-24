<?php

declare(strict_types=1);

$dbUser = getenv('TEST_STEFANO_DB_USER');
if ($dbUser) {
    define('TEST_STEFANO_DB_USER', $dbUser);
}

$dbHost = getenv('TEST_STEFANO_DB_HOSTNAME');
if ($dbHost) {
    define('TEST_STEFANO_DB_HOSTNAME', $dbHost);
}

$dbVendor = getenv('DB');

switch ($dbVendor) {
    case 'pgsql':
        define('TEST_STEFANO_DB_VENDOR', 'pgsql');

        break;

    case 'mysql':
        define('TEST_STEFANO_DB_VENDOR', 'mysql');

        break;

    default:
        throw new \Exception(sprintf('Wrong DB environment variable "%s"', $dbVendor));
}

$treeAdapter = getenv('ADAPTER');

switch ($treeAdapter) {
    case 'pdo':
        define('TEST_STEFANO_ADAPTER', 'pdo');

        break;

    case 'zend1':
        define('TEST_STEFANO_ADAPTER', 'zend1');

        break;

    case 'zend2':
        define('TEST_STEFANO_ADAPTER', 'zend2');

        break;

    case 'doctrine2-dbal':
        define('TEST_STEFANO_ADAPTER', 'doctrine2-dbal');

        break;

    default:
        throw new \Exception(sprintf('Wrong ADAPTER environment variable "%s"', $treeAdapter));
}

echo PHP_EOL;
echo '------- TEST CONFIG -------'.PHP_EOL;
echo sprintf('Database vendor: "%s"', $dbVendor).PHP_EOL;
echo sprintf('Adapter        : "%s"', $treeAdapter).PHP_EOL;
echo PHP_EOL;

unset($dbUser, $dbHost, $dbVendor, $treeAdapter);

include_once __DIR__.'/../vendor/autoload.php';

include_once __DIR__.'/testConfig.php';
