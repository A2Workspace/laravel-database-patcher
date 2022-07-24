<?php

namespace Tests;

use Illuminate\Testing\PendingCommand;
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

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ian')->unique();
            $table->string('name');
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
        @unlink(static::$published);
        @rmdir(static::$published);
    }

    private function expectsCommandChoice(PendingCommand $command, $answer): PendingCommand
    {
        $options = [
            $this->parseLabel('2022_07_19_000000_add_priority_to_products_table.php'),
        ];

        $command->expectsChoice(
            '選擇補丁檔案',
            $this->parseLabel($answer),
            $options,
        );

        return $command;
    }

    // =========================================================================
    // = Tests
    // =========================================================================

    public function test_call_artisan_command()
    {
        $command = $this->artisan('db:patch');

        $this->expectsCommandChoice(
            $command,
            '2022_07_19_000000_add_priority_to_products_table.php'
        );

        $command->expectsOutput(sprintf(
            'Running: php artisan migrate --path=%s',
            $this->resolvePath('/database/patches/2022_07_19_000000_add_priority_to_products_table.php')
        ));

        $command->run();

        $this->assertDatabaseHas(
            'migrations',
            ['migration' => '2022_07_19_000000_add_priority_to_products_table']
        );

        $this->assertDatabaseTableHasColumn('products', 'priority');
    }

    //  @depends

    public function test_call_artisan_command_with_filter()
    {
        $command = $this->artisan('db:patch', [
            'filter' => 'product',
        ]);

        $this->expectsCommandChoice(
            $command,
            '2022_07_19_000000_add_priority_to_products_table.php'
        );

        $command->expectsOutput(sprintf(
            'Running: php artisan migrate --path=%s',
            $this->resolvePath('/database/patches/2022_07_19_000000_add_priority_to_products_table.php')
        ));

        $command->run();

        $this->assertDatabaseHas(
            'migrations',
            ['migration' => '2022_07_19_000000_add_priority_to_products_table']
        );

        $this->assertDatabaseTableHasColumn('products', 'priority');
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
}
