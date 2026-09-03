<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the plans required by the auth flow
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    // ─────────────────────────────────────────────────────────────
    // Register
    // ─────────────────────────────────────────────────────────────

    public function test_register_page_loads(): void
    {
        $this->get('/register')->assertOk()->assertSee('สมัครสมาชิก');
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // terms NOT included
        ])->assertSessionHasErrors(['terms']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->post('/register', [
            'name' => 'Another',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ])->assertSessionHasErrors(['email']);
    }

    public function test_registered_user_is_assigned_free_plan(): void
    {
        $this->post('/register', [
            'name' => 'Plan Test',
            'email' => 'plantest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ]);

        $user = User::where('email', 'plantest@example.com')->first();
        $this->assertNotNull($user->plan_id);
        $this->assertEquals('free', $user->getActivePlan()->name);
    }

    // ─────────────────────────────────────────────────────────────
    // Login / Logout
    // ─────────────────────────────────────────────────────────────

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk()->assertSee('เข้าสู่ระบบ');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct_password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong_password',
        ])->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    // ─────────────────────────────────────────────────────────────
    // Auth middleware
    // ─────────────────────────────────────────────────────────────

    public function test_unauthenticated_users_are_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_are_redirected_from_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
    }
}
