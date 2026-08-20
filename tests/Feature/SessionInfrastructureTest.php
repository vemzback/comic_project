<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SessionInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_session_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('sessions'));
    }
}
