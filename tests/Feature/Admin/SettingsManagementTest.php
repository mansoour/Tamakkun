<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'force_password_change' => '1',
            'enable_gamification' => '1',
            'email_notifications' => '1',
            'platform_name' => 'تمكّن',
            'tagline' => 'عبارة الاختبار',
            'weekly_content_goal' => 6,
            'default_target_score' => 85,
            'inactivity_days' => 7,
            'upcoming_exam_alert_days' => 14,
            'low_activity_threshold' => 2,
            'support_email' => '',
            'support_phone' => '',
            'privacy_url' => '',
            'terms_url' => '',
            'about_text' => '',
            'privacy_text' => '',
            'terms_text' => '',
        ], $overrides);
    }

    public function test_admin_sees_every_editable_setting(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('name="inactivity_days"', false)
            ->assertSee('name="upcoming_exam_alert_days"', false)
            ->assertSee('name="low_activity_threshold"', false)
            ->assertSee('name="default_target_score"', false)
            ->assertSee('name="support_email"', false)
            ->assertSee('name="privacy_text"', false)
            ->assertSee('name="terms_url"', false);
    }

    public function test_admin_saves_thresholds_contacts_and_page_text(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/admin/settings', $this->payload([
                'inactivity_days' => 10,
                'upcoming_exam_alert_days' => 21,
                'low_activity_threshold' => 3,
                'default_target_score' => 90,
                'support_email' => 'help@school.test',
                'support_phone' => '+966 11 000 0000',
                'privacy_text' => "الفقرة الأولى\n\nالفقرة الثانية",
                'terms_url' => 'https://school.test/terms',
            ]))
            ->assertRedirect('/admin/settings')
            ->assertSessionHasNoErrors();

        $settings = app(SettingsService::class);
        $settings->flush();
        $this->assertSame(10, $settings->get('inactivity_days'));
        $this->assertSame(21, $settings->get('upcoming_exam_alert_days'));
        $this->assertSame(3, $settings->get('low_activity_threshold'));
        $this->assertSame(90, $settings->get('default_target_score'));
        $this->assertSame('help@school.test', $settings->get('support_email'));
        $this->assertSame("الفقرة الأولى\n\nالفقرة الثانية", $settings->get('privacy_text'));
        $this->assertSame('https://school.test/terms', $settings->get('terms_url'));
        $this->assertNull($settings->get('about_text'));
        $this->assertSame('settings.updated', AuditLog::latest('id')->first()->action);
    }

    public function test_invalid_values_are_rejected_with_arabic_messages(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from('/admin/settings')
            ->put('/admin/settings', $this->payload([
                'inactivity_days' => 0,
                'default_target_score' => 101,
                'support_email' => 'not-an-email',
                'support_phone' => 'call me',
                'privacy_url' => 'http://insecure.test',
                'platform_name' => '',
            ]))
            ->assertRedirect('/admin/settings')
            ->assertSessionHasErrors(['inactivity_days', 'default_target_score', 'support_email', 'support_phone', 'privacy_url', 'platform_name']);

        $this->assertSame(7, app(SettingsService::class)->get('inactivity_days'));
    }

    public function test_platform_name_and_tagline_appear_on_public_pages(): void
    {
        app(SettingsService::class)->setMany(['platform_name' => 'منصة المدرسة', 'tagline' => 'عبارة مخصصة للاختبار']);

        $this->get('/')->assertOk()->assertSee('منصة المدرسة')->assertSee('عبارة مخصصة للاختبار')
            ->assertSee('<title>منصة المدرسة</title>', false);
        $this->get('/login')->assertSee('عبارة مخصصة للاختبار');
    }
}
