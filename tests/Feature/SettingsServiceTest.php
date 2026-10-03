<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_come_from_config_when_nothing_is_stored(): void
    {
        $this->assertSame(7, app(SettingsService::class)->get('inactivity_days'));
        $this->assertSame('fallback', app(SettingsService::class)->get('missing_key', 'fallback'));
    }

    public function test_stored_values_override_defaults_and_keep_their_type(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('inactivity_days', 10);
        $settings->set('enable_gamification', false);

        $this->assertSame(10, $settings->get('inactivity_days'));
        $this->assertFalse($settings->get('enable_gamification'));
    }

    public function test_settings_are_cached_and_the_cache_clears_after_save(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame(7, $settings->get('inactivity_days'));
        $this->assertTrue(Cache::has(SettingsService::CACHE_KEY));

        // A change that bypasses the service is not visible while cached...
        Setting::create(['key' => 'inactivity_days', 'value' => '30']);
        $this->assertSame(7, $settings->get('inactivity_days'));

        // ...but saving through the service clears the cache.
        $settings->set('upcoming_exam_alert_days', 21);

        $this->assertSame(30, $settings->get('inactivity_days'));
        $this->assertSame(21, $settings->get('upcoming_exam_alert_days'));
    }

    public function test_saving_settings_writes_an_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        app(SettingsService::class)->set('inactivity_days', 5);

        $log = AuditLog::sole();
        $this->assertSame('settings.updated', $log->action);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['inactivity_days' => 7], $log->old_values);
        $this->assertSame(['inactivity_days' => 5], $log->new_values);
    }

    public function test_unknown_settings_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SettingsService::class)->set('not_a_setting', 1);
    }
}
