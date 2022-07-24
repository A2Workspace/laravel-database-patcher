<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CallDbPatchArtisanCommandTest extends TestCase
{
    use DatabaseMigrations;

    protected static $published;

    protected function setUp(): void
    {
        parent::setUp();

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

    /**
     * @param  string  $option
     * @return string
     */
    private function parseLabel(string $option): string
    {
        return "-> {$option}";
    }

    /**
     * @param  string  $table
     * @param  string  $column
     * @return self
     */
    private function assertDatabaseTableHasColumn($table, $column)
    {
        $this->assertTrue(
            Schema::hasColumn($table, $column),
            sprintf(
                'The table [%s] doesn\'t have the column named %s',
                $table,
                $column
            )
        );

        return $this;
    }

    // =========================================================================
    // = Tests
    // =========================================================================

    public function test_call_artisan_command()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ian')->unique();
            $table->string('name');
        });

        $command = $this->artisan('db:patch');

        $command->expectsChoice(
            '選擇補丁檔案',
            $this->parseLabel('2022_07_19_000000_add_priority_to_products_table.php'),
            [
                $this->parseLabel('2022_07_19_000000_add_priority_to_products_table.php'),
            ],
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
}
