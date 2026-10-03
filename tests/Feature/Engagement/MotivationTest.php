<?php

namespace Tests\Feature\Engagement;

use App\Models\Motivation;
use App\Models\User;
use App\Services\MotivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MotivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_dated_today_wins_over_rotation(): void
    {
        Motivation::factory()->count(3)->create();
        $dated = Motivation::factory()->create(['title' => 'دفعة-اليوم', 'publish_date' => today()]);

        $this->assertTrue(app(MotivationService::class)->today()->is($dated));

        $this->actingAs(User::factory()->student()->create())->get('/student/motivation')
            ->assertOk()->assertSee('دفعة-اليوم');
    }

    public function test_rotation_is_stable_for_the_day_and_skips_inactive_and_future_items(): void
    {
        $pool = Motivation::factory()->count(3)->create();
        Motivation::factory()->create(['is_active' => false, 'title' => 'موقوفة']);
        Motivation::factory()->create(['publish_date' => today()->addWeek(), 'title' => 'مستقبلية']);

        $today = app(MotivationService::class)->today();
        $this->assertTrue($pool->contains($today));
        $this->assertTrue(app(MotivationService::class)->today()->is($today));

        $this->actingAs(User::factory()->student()->create())->get('/student/motivation')
            ->assertDontSee('موقوفة')->assertDontSee('مستقبلية');
    }

    public function test_admin_can_create_motivations_with_whitelisted_video_or_webp_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/motivations', [
            'title' => 'مقطع', 'content' => 'نص', 'media_type' => 'video', 'video_url' => 'https://evil.example/v', 'is_active' => '1',
        ])->assertSessionHasErrors('video_url');

        $this->actingAs($admin)->post('/admin/motivations', [
            'title' => 'صورة', 'content' => 'نص', 'media_type' => 'image', 'is_active' => '1',
            'image' => UploadedFile::fake()->image('m.png', 800, 600),
        ])->assertRedirect('/admin/motivations');

        $path = Motivation::sole()->image_path;
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'motivation.created']);
    }

    public function test_counselors_cannot_manage_motivations(): void
    {
        $this->actingAs(User::factory()->counselor()->create())->get('/admin/motivations')->assertForbidden();
    }
}
