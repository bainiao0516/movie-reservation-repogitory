<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\DuskTestCase;
use Tests\TestCase;

pest()->extend(DuskTestCase::class, DatabaseMigrations::class)
    ->in('Browser');

pest()->extend(TestCase::class)->in('Feature');
