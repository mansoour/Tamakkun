<?php

namespace Tests\Feature;

use App\Models\ImportantLink;
use App\Models\Source;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_reachable_without_login(): void
    {
        foreach (['/about', '/resources', '/privacy', '/terms'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('<html lang="ar" dir="rtl">', false);
        }
    }

    public function test_about_page_uses_admin_text_and_support_contacts(): void
    {
        $this->get('/about')->assertSee('منصة تساعد طالبات الصف الثالث الثانوي')->assertDontSee('mailto:');

        app(SettingsService::class)->setMany([
            'about_text' => "نص تعريفي أول\n\nنص <b>ثانٍ</b>",
            'support_email' => 'help@school.test',
        ]);

        $this->get('/about')
            ->assertSee('نص تعريفي أول')
            ->assertSee('نص &lt;b&gt;ثانٍ&lt;/b&gt;', false)
            ->assertSee('mailto:help@school.test', false)
            ->assertDontSee('منصة تساعد طالبات الصف الثالث الثانوي');
    }

    public function test_unwritten_legal_pages_say_coming_soon_and_are_not_linked(): void
    {
        $this->get('/privacy')->assertSee('قريبًا');
        $this->get('/')->assertDontSee(route('privacy'), false)->assertSee(route('about'), false);

        app(SettingsService::class)->setMany(['privacy_text' => 'نص الخصوصية المعتمد', 'terms_url' => 'https://school.test/terms']);

        $this->get('/privacy')->assertSee('نص الخصوصية المعتمد')->assertDontSee('قريبًا');
        $this->get('/terms')->assertSee('https://school.test/terms', false);
        $this->get('/')->assertSee(route('privacy'), false)->assertSee(route('terms'), false);
    }

    public function test_resources_page_lists_only_active_official_links_and_verified_sources(): void
    {
        ImportantLink::factory()->create(['title' => 'خدمة رسمية ظاهرة', 'is_official' => true]);
        ImportantLink::factory()->create(['title' => 'رابط غير رسمي', 'is_official' => false]);
        ImportantLink::factory()->create(['title' => 'رابط رسمي معطّل', 'is_official' => true, 'is_active' => false]);
        Source::factory()->create(['name' => 'مصدر موثّق', 'website_url' => 'https://source.test']);
        Source::factory()->create(['name' => 'مصدر بلا رابط']);

        $this->get('/resources')
            ->assertSee('خدمة رسمية ظاهرة')
            ->assertDontSee('رابط غير رسمي')
            ->assertDontSee('رابط رسمي معطّل')
            ->assertSee('مصدر موثّق')
            ->assertDontSee('مصدر بلا رابط');
    }

    public function test_web_app_manifest_uses_the_platform_name_and_existing_icons(): void
    {
        app(SettingsService::class)->set('platform_name', 'منصة المدرسة');

        $response = $this->get('/manifest.webmanifest')->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'منصة المدرسة')
            ->assertJsonPath('dir', 'rtl');

        foreach ($response->json('icons') as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }

        $this->get('/')->assertSee(route('manifest'), false);
    }
}
