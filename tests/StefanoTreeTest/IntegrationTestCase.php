<?php
namespace StefanoTreeTest;


abstract class IntegrationTestCase
    extends \PHPUnit_Extensions_Database_TestCase
{
    protected function getConnection() {
        return $this->createDefaultDBConnection(TestUtil::getPDOConnection());
    }
}