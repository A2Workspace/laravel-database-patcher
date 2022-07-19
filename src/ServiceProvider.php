<?php

namespace A2Workspace\DatabasePatcher;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../publishes/patches' => database_path('patches'),
        ], '@a2workspace/laravel-database-patcher');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Commands\DbPatchCommand::class,
        ]);
    }
}
