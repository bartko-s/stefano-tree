<?php

declare(strict_types=1);

/*
 * Constants the test suite defines at runtime (values depend on the DB and
 * ADAPTER environment variables, set by the composer test scripts). Declared
 * here so PHPStan can discover them and does not treat them as fixed
 * literals.
 */
if (!defined('TEST_STEFANO_DB_VENDOR')) {
    $dbVendor = getenv('DB');

    define('TEST_STEFANO_DB_VENDOR', is_string($dbVendor) ? $dbVendor : '');
}

if (!defined('TEST_STEFANO_ADAPTER')) {
    $treeAdapter = getenv('ADAPTER');
    define('TEST_STEFANO_ADAPTER', is_string($treeAdapter) ? $treeAdapter : '');
}