<?php

namespace Tests\Feature;

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFilesAndTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_dashboard_files_requires_authentication(): void
    {
        $this->get('/dashboard/files')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_their_files(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::factory()->forUser($user)->create([
            'original_name' => 'my_test_document.pdf',
        ]);

        $response = $this->actingAs($user)->get('/dashboard/files');

        $response->assertOk()
            ->assertSee('คลังไฟล์ของฉัน')
            ->assertSee('my_test_document.pdf');
    }

    public function test_user_can_delete_their_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->delete("/dashboard/files/{$file->id}");

        $response->assertRedirect('/dashboard/files');
        $this->assertSoftDeleted('uploaded_files', ['id' => $file->id]);
    }

    public function test_public_user_can_view_templates_library(): void
    {
        $response = $this->get('/templates');

        $response->assertOk()
            ->assertSee('คลังแบบฟอร์มเอกสาร')
            ->assertSee('แบบข้อตกลงในการพัฒนางาน (PA 1/ส)')
            ->assertSee('สัญญาจ้างงานพนักงานประจำ')
            ->assertSee('หนังสือมอบอำนาจทั่วไป');
    }
}
