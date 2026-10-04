<?php

namespace App\Http\Controllers;

use App\Services\PublicPageService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function __construct(private readonly PublicPageService $pages) {}

    public function home(): View
    {
        return view('welcome', ['stats' => $this->pages->homeStats()]);
    }

    public function about(): View
    {
        return view('public.about', [
            'paragraphs' => $this->pages->aboutParagraphs(),
            'support' => $this->pages->supportContacts(),
        ]);
    }

    public function resources(): View
    {
        return view('public.resources', [
            'links' => $this->pages->officialLinks(),
            'sources' => $this->pages->verifiedSources(),
        ]);
    }

    /**
     * Web app manifest so the site can be added to a phone's home screen.
     * There is no service worker and no offline mode: pages are always live.
     */
    public function manifest(SettingsService $settings): JsonResponse
    {
        $name = $settings->get('platform_name') ?: config('tamakkun.settings.platform_name');

        return response()->json([
            'name' => $name,
            'short_name' => $name,
            'description' => $settings->get('tagline'),
            'lang' => 'ar',
            'dir' => 'rtl',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#FFFFFF',
            'theme_color' => '#7458B5',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function privacy(): View
    {
        return view('public.legal', ['page' => $this->pages->legalPage('privacy')]);
    }

    public function terms(): View
    {
        return view('public.legal', ['page' => $this->pages->legalPage('terms')]);
    }
}
