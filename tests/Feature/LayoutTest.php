<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
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

    public function test_every_navigation_item_points_to_a_real_route(): void
    {
        foreach (['student', 'counselor', 'admin'] as $area) {
            foreach (Navigation::for($area) as $item) {
                if ($item['route'] !== null) {
                    $this->assertTrue(Route::has($item['route']), "Missing route {$item['route']}");
                }
            }
        }

        // No fake buttons anywhere: nothing links to "#".
        $this->actingAs(User::factory()->admin()->create())->get('/admin/dashboard')->assertDontSee('href="#"', false);
    }

    public function test_navigation_only_links_to_permitted_sections(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertSee(route('admin.schools.index'), false)
            ->assertSee(route('admin.imports.create'), false);

        $limited = User::factory()->create();
        $limited->givePermissionTo(PermissionName::ACCESS_ADMIN_AREA->value);

        $this->actingAs($limited)
            ->get('/admin/dashboard')
            ->assertDontSee(route('admin.schools.index'), false)
            ->assertDontSee(route('admin.students.index'), false);
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
