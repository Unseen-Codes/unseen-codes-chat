<?php

uses(\Orchestra\Testbench\TestCase::class)->in('Feature', 'Unit');

uses()->beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
})->in('Feature');
