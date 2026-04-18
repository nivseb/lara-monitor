<?php

namespace Tests;

use PHPUnit\Framework\Assert;

pest()
    ->uses(ComponentTestCase::class)
    ->in('Component');
pest()
    ->uses(UnitTestCase::class)
    ->in('Unit');
pest()
    ->uses(IntegrationTestCase::class)
    ->in('Integration');
