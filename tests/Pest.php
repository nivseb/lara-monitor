<?php

namespace Tests;

pest()
    ->uses(ComponentTestCase::class)
    ->in('Component');
pest()
    ->uses(UnitTestCase::class)
    ->in('Unit');
