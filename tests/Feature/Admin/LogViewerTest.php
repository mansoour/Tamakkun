<?php

namespace Tests\Feature\Admin;

use App\Models\EmailLog;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_and_filter_the_audit_log(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'مديرة-السجل']);
        $this->actingAs($admin);
        app(SettingsService::class)->set('inactivity_days', 9);

        $this->get('/admin/audit-logs')->assertOk()->assertSee('settings.updated')->assertSee('مديرة-السجل');
        $this->get('/admin/audit-logs?action=school')->assertOk()->assertDontSee('settings.updated');
    }

    public function test_admin_can_browse_the_email_log(): void
    {
        EmailLog::create(['recipient' => 'a@example.com', 'subject' => 'موضوع-تجريبي', 'status' => 'failed', 'error_message' => 'خطأ']);

        $this->actingAs(User::factory()->admin()->create())->get('/admin/email-logs?status=failed')
            ->assertOk()->assertSee('موضوع-تجريبي')->assertSee('فشل الإرسال');
    }

    public function test_counselors_cannot_see_logs(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($counselor)->get('/admin/email-logs')->assertForbidden();
    }
}
