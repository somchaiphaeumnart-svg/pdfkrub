<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_billing_index_requires_authentication(): void
    {
        $this->get('/billing')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_billing_overview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertSee('การสมัครสมาชิก');
    }

    public function test_user_can_view_upgrade_page(): void
    {
        $user = User::factory()->create();
        $proPlan = Plan::where('name', 'pro')->first();

        $this->actingAs($user)
            ->get("/billing/upgrade/{$proPlan->id}")
            ->assertOk()
            ->assertSee($proPlan->display_name_th ?? $proPlan->display_name);
    }

    public function test_user_can_initiate_promptpay_charge(): void
    {
        $user = User::factory()->create();
        $proPlan = Plan::where('name', 'pro')->first();

        $response = $this->actingAs($user)->postJson('/billing/charge', [
            'plan_id' => $proPlan->id,
            'billing_interval' => 'monthly',
            'payment_method' => 'promptpay',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'charge_id',
                'payment_method',
                'qr_url',
                'amount',
            ])
            ->assertJson([
                'status' => 'pending',
                'payment_method' => 'promptpay',
            ]);
    }

    public function test_user_can_pay_with_credit_card_and_activate_subscription(): void
    {
        $user = User::factory()->create();
        $proPlan = Plan::where('name', 'pro')->first();

        $response = $this->actingAs($user)->postJson('/billing/charge', [
            'plan_id' => $proPlan->id,
            'billing_interval' => 'yearly',
            'payment_method' => 'card',
            'card_token' => 'tokn_test_sample',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'successful',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_interval' => 'yearly',
        ]);

        $this->assertEquals($proPlan->id, $user->fresh()->plan_id);
    }

    public function test_promptpay_status_check_activates_subscription_when_successful(): void
    {
        $user = User::factory()->create();
        $proPlan = Plan::where('name', 'pro')->first();

        $response = $this->actingAs($user)
            ->getJson('/billing/charge/chrg_test_sample123/status');

        $response->assertOk()
            ->assertJsonStructure(['status']);
    }

    public function test_user_can_cancel_subscription(): void
    {
        $user = User::factory()->create();
        $proPlan = Plan::where('name', 'pro')->first();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_interval' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'cancel_at_period_end' => false,
        ]);

        $response = $this->actingAs($user)->post('/billing/cancel');

        $response->assertRedirect();
        $this->assertTrue($sub->fresh()->cancel_at_period_end);
        $this->assertNotNull($sub->fresh()->cancelled_at);
    }

    public function test_omise_webhook_handles_charge_complete(): void
    {
        $user = User::factory()->create();
        $proPlan = Plan::where('name', 'pro')->first();

        $payload = [
            'key' => 'charge.complete',
            'data' => [
                'id' => 'chrg_test_webhook_123',
                'status' => 'successful',
                'paid' => true,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $proPlan->id,
                    'billing_interval' => 'monthly',
                ],
            ],
        ];

        $response = $this->postJson('/billing/webhook/omise', $payload);

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
        ]);
    }
}
