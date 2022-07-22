<?php

namespace Tests;

use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class CallDbPatchArtisanCommandTest extends TestCase
{
    private function expectsCommandChoice(PendingCommand $command, $input): PendingCommand
    {
        $options = [
            '2022_07_19_000000_add_priority_to_products_table.php',
            '2022_07_19_000000_add_priority_to_products_table.php',
        ];

        $options = array_map(fn ($option) => $this->parseLabel($option), $options);

        if (is_string($input)) {
            $answer = $this->parseLabel($input);
        } else if (is_integer($input)) {
            $answer = $options[$input];
        }

        return $command->expectsChoice(
            '選擇補丁檔案',
            $answer,
            $options,
        );
    }

    private function parseLabel(string $option): string
    {
        return "-> {$option}";
    }

    // =========================================================================
    // = Tests
    // =========================================================================

    public function test_call_artisan_command()
    {
        $command = $this->artisan('db:patch');

        $this->expectsCommandChoice($command, 0);
    }
}
