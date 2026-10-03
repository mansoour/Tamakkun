<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_are_arabic_and_right_to_left(): void
    {
        foreach (['/', '/login'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('<html lang="ar" dir="rtl">', false);
        }

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertSee('<html lang="ar" dir="rtl">', false);
    }

    public function test_home_page_shows_tagline_and_login_entry_points(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('خطوتك اليوم… تصنع نتيجتك غدًا')
            ->assertSee('دخول الطالبة')
            ->assertSee('دخول الموجهة الطلابية');
    }

    public function test_planned_sections_are_not_rendered_as_links(): void
    {
        $response = $this->actingAs(User::factory()->student()->create())->get('/student/dashboard');

        $response->assertSee('قريبًا');
        // "Planned" items must never point anywhere — no fake buttons.
        $response->assertDontSee('href="#"', false);
    }

    public function test_icon_component_renders_heroicon_paths(): void
    {
        $html = Blade::render('<x-icon name="home" class="h-4 w-4" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('h-4 w-4', $html);
        $this->assertStringContainsString(config('icons.home')[0], $html);
    }

    public function test_icon_component_can_be_labelled_for_screen_readers(): void
    {
        $html = Blade::render('<x-icon name="bell" label="الإشعارات" />');

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('aria-label="الإشعارات"', $html);
    }

    public function test_unknown_icon_fails_loudly(): void
    {
        $this->expectExceptionMessage('Unknown icon [does-not-exist]');

        Blade::render('<x-icon name="does-not-exist" />');
    }
}
