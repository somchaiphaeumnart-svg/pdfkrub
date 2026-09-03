<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_pdpa_page_loads_successfully(): void
    {
        $response = $this->get('/pdpa');

        $response->assertOk()
            ->assertSee('นโยบายคุ้มครองข้อมูลส่วนบุคคล')
            ->assertSee('เซิร์ฟเวอร์ในประเทศไทย')
            ->assertSee('ลบไฟล์อัตโนมัติ (1 ชม.)');
    }

    public function test_privacy_page_loads_successfully(): void
    {
        $response = $this->get('/privacy');

        $response->assertOk()
            ->assertSee('นโยบายความเป็นส่วนตัว')
            ->assertSee('การลบไฟล์เอกสารอัตโนมัติ');
    }

    public function test_pricing_page_loads_successfully(): void
    {
        $response = $this->get('/pricing');

        $response->assertOk()
            ->assertSee('แผนราคาที่')
            ->assertSee('สำหรับครู')
            ->assertSee('390');
    }
}
