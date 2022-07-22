<?php

namespace Tests;

use Closure;
use A2Workspace\DatabasePatcher\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.patcher.paths', [
            __DIR__ . '/fixtures/patches',
            __DIR__ . '/../publishes/patches',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    // =========================================================================
    // = Helpers
    // =========================================================================

    private function refMethod($name, $scope): Closure
    {
        $ref = function () use ($name) {
            return $this->$name(...func_get_args());
        };

        return $ref->bindTo($scope, $scope);
    }
}
