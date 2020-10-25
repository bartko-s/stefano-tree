<?php

declare(strict_types=1);

namespace StefanoTreeTest;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use StefanoTreeTest\DbTester\DbTestCaseTrait;

abstract class IntegrationTestCase extends TestCase
{
    use DbTestCaseTrait;
    use MockeryPHPUnitIntegration;

    protected function getPdoConnection()
    {
        return TestUtil::getPDOConnection();
    }

    public function recreateDbScheme()
    {
        TestUtil::createDbScheme();
    }
}
