<?php

namespace Tests;

use Illuminate\Support\Facades\Schema;

trait TestArtisanCommandHelpers
{
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

    /**
     * @param  string  $table
     * @param  string  $column
     * @return self
     */
    private function assertDatabaseTableMissingColumn($table, $column)
    {
        $this->assertFalse(
            Schema::hasColumn($table, $column),
            sprintf(
                'The table [%s] have the column named %s',
                $table,
                $column
            )
        );

        return $this;
    }
}
