<?php

namespace Tests;

use Closure;
use A2Workspace\DatabasePatcher\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        // ...
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    // =========================================================================
    // = Helpers
    // =========================================================================

    /**
     * 生成能執行被保護方法的反射函數
     *
     * @param  string  $name
     * @param  object  $scope
     * @return \Closure
     */
    protected function refMethod($name, $scope): Closure
    {
        $ref = function () use ($name) {
            return $this->$name(...func_get_args());
        };

        return $ref->bindTo($scope, $scope);
    }

    /**
     * 處理不同作業系統下的路徑
     *
     * @param  string  $path
     * @return string
     */
    protected function resolvePath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
