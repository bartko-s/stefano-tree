<?php

declare(strict_types=1);

namespace StefanoTreeTest;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;
}
