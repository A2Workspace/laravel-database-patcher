<?php

namespace Tests;

use A2Workspace\DatabasePatcher\Commands\DbPatchCommand;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;
use Mockery as m;

class DbPatchCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    private function makeCommand(): DbPatchCommand
    {
        /** @var \Illuminate\Filesystem\Filesystem $fs */
        $fs = m::mock(Filesystem::class);

        $command = new DbPatchCommand($fs);
        $command->setLaravel($this->app);

        return $command;
    }

    // =========================================================================
    // = Tests
    // =========================================================================

    public function test_example()
    {
        $this->assertTrue(true);
    }
}
