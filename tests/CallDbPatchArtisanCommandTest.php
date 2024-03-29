<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class CallDbPatchArtisanCommandTest extends TestCase
{
    use TestArtisanCommandHelpers;

    protected static $published;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username');
        });

        if (empty(static::$published)) {
            Artisan::call('vendor:publish', [
                '--tag' => '@a2workspace/laravel-database-patcher',
            ]);

            static::$published = database_path('patches');
        }
    }

    public static function tearDownAfterClass(): void
    {
        (new Filesystem)->cleanDirectory(static::$published);
    }

    // =========================================================================
    // = Tests
    // =========================================================================

    public function test_call_artisan_command()
    {
        $command = $this->artisan('db:patch');

        $command->expectsChoice(
            '選擇補丁檔案',
            $this->parseLabel('2022_07_19_000000_add_soft_deletes_to_users_table'),
            [
                $this->parseLabel('2022_07_19_000000_add_soft_deletes_to_users_table')
            ],
        );

        $command->expectsOutput(sprintf(
            'Running: php artisan migrate --path=%s',
            $this->resolvePath('/database/patches/2022_07_19_000000_add_soft_deletes_to_users_table.php')
        ));

        $command->assertExitCode(0);
        $command->run();

        $this->assertDatabaseHas(
            'migrations',
            ['migration' => '2022_07_19_000000_add_soft_deletes_to_users_table']
        );

        $this->assertDatabaseTableHasColumn('users', 'deleted_at');
    }

    public function test_call_artisan_command_and_revert()
    {
        $this->artisan('db:patch')
            ->expectsChoice(
                '選擇補丁檔案',
                $this->parseLabel('2022_07_19_000000_add_soft_deletes_to_users_table'),
                [
                    $this->parseLabel('2022_07_19_000000_add_soft_deletes_to_users_table')
                ],
            )
            ->assertExitCode(0)
            ->run();

        $command2 = $this->artisan('db:patch', ['--revert' => true]);

        $command2->expectsChoice(
            '選擇補丁檔案',
            $this->parseInstalledLabel('2022_07_19_000000_add_soft_deletes_to_users_table'),
            [
                $this->parseInstalledLabel('2022_07_19_000000_add_soft_deletes_to_users_table')
            ],
        );

        $command2->expectsOutput(sprintf(
            'Running: php artisan migrate:rollback --path=%s',
            $this->resolvePath('/database/patches/2022_07_19_000000_add_soft_deletes_to_users_table.php')
        ));

        $command2->assertExitCode(0);
        $command2->run();

        $this->assertDatabaseTableMissingColumn('users', 'deleted_at');

        $this->assertDatabaseMissing(
            'migrations',
            ['migration' => '2022_07_19_000000_add_soft_deletes_to_users_table']
        );
    }

    public function test_call_artisan_command_with_filter()
    {
        $command = $this->artisan('db:patch', [
            'filter' => 'users',
        ]);

        $command->expectsChoice(
            '選擇補丁檔案',
            $this->parseLabel('2022_07_19_000000_add_soft_deletes_to_users_table'),
            [
                $this->parseLabel('2022_07_19_000000_add_soft_deletes_to_users_table')
            ],
        );

        $command->expectsOutput(sprintf(
            'Running: php artisan migrate --path=%s',
            $this->resolvePath('/database/patches/2022_07_19_000000_add_soft_deletes_to_users_table.php')
        ));

        $command->assertExitCode(0);
        $command->run();

        $this->assertDatabaseHas(
            'migrations',
            ['migration' => '2022_07_19_000000_add_soft_deletes_to_users_table']
        );

        $this->assertDatabaseTableHasColumn('users', 'deleted_at');
    }

    public function test_call_artisan_command_with_filter_then_not_found()
    {
        $command = $this->artisan('db:patch', [
            'filter' => '__IMIFUMEI__',
        ]);

        $command->expectsOutput('找不到符合的補丁檔案');
        $command->assertExitCode(1);
        $command->run();
    }

    public function test_call_artisan_command_when_directory_is_empty()
    {
        $this->app->config->set('database.patcher.paths', []);

        $command = $this->artisan('db:patch');

        $command->expectsOutput('找不到任何補丁檔案');
        $command->assertExitCode(1);
        $command->run();
    }
}
