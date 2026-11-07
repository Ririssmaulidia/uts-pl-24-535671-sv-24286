<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_200_when_database_and_cache_are_ok(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok')
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'database' => ['status', 'latency_ms'],
                    'cache' => ['status', 'latency_ms'],
                ],
                'timestamp',
            ]);
    }

    public function test_health_returns_503_and_names_the_failing_component_when_database_is_down(): void
    {
        // Meniru basis data yang tidak bisa dihubungi (kode galat MySQL 2002).
        DB::shouldReceive('select')
            ->once()
            ->andThrow(new PDOException('SQLSTATE[HY000] [2002] Connection refused', 2002));

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.database.status', 'down')
            ->assertJsonPath('checks.database.reason', 'database_unreachable')
            ->assertJsonPath('checks.cache.status', 'ok')
            // Kode galat hanya untuk log, tidak dibocorkan ke klien.
            ->assertJsonMissingPath('checks.database.error_code');
    }

    public function test_health_does_not_require_a_token(): void
    {
        $this->getJson('/health')->assertStatus(200);
    }
}
