<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_api_health_check_returns_healthy(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'healthy',
                'app' => config('app.name'),
                'version' => '1.0.0',
            ]);
    }

    public function test_api_plans_returns_active_plans(): void
    {
        $response = $this->getJson('/api/v1/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'display_name', 'price_monthly', 'max_file_size_mb'],
                ],
            ]);
    }

    public function test_api_tools_returns_tool_registry(): void
    {
        $response = $this->getJson('/api/v1/tools');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['slug', 'name', 'category', 'description_th'],
                ],
            ]);
    }
}
