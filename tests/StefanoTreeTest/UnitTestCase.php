<?php

namespace StefanoTreeTest;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;
}
