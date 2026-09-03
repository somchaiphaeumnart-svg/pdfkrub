<?php

namespace Tests\Feature;

use App\Models\PdfJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_user_can_access_admin_dashboard_and_see_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Create some sample data
        PdfJob::factory()->create([
            'tool_name' => 'merge-pdf',
            'status' => PdfJob::STATUS_DONE,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk()
            ->assertSee('แผงควบคุมระบบ (Admin)')
            ->assertSee('ผู้ใช้งานทั้งหมด')
            ->assertSee('เครื่องมือยอดนิยม');
    }
}
