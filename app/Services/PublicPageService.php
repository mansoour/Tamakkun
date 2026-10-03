<?php

namespace App\Services;

use App\Models\ImportantLink;
use App\Models\Source;
use Illuminate\Support\Collection;

/**
 * Data for the lightweight public pages (/about, /resources, /privacy,
 * /terms). Everything shown comes from admin-entered settings or records;
 * nothing private and nothing invented is ever exposed here.
 */
class PublicPageService
{
    /**
     * Legal pages and the settings that hold their text and external link.
     */
    public const LEGAL_PAGES = [
        'privacy' => ['title' => 'سياسة الخصوصية', 'text' => 'privacy_text', 'url' => 'privacy_url'],
        'terms' => ['title' => 'الشروط والأحكام', 'text' => 'terms_text', 'url' => 'terms_url'],
    ];

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array{title: string, paragraphs: list<string>, url: string|null}
     */
    public function legalPage(string $page): array
    {
        $definition = self::LEGAL_PAGES[$page];

        return [
            'title' => $definition['title'],
            'paragraphs' => $this->paragraphs($this->settings->get($definition['text'])),
            'url' => $this->settings->get($definition['url']),
        ];
    }

    /**
     * Admin-written about text, split into paragraphs (empty when not written yet).
     *
     * @return list<string>
     */
    public function aboutParagraphs(): array
    {
        return $this->paragraphs($this->settings->get('about_text'));
    }

    /**
     * @return array{email: string|null, phone: string|null}
     */
    public function supportContacts(): array
    {
        return ['email' => $this->settings->get('support_email'), 'phone' => $this->settings->get('support_phone')];
    }

    /**
     * Active links an admin marked as official; these are public services.
     *
     * @return Collection<int, ImportantLink>
     */
    public function officialLinks(): Collection
    {
        return ImportantLink::where('is_active', true)->where('is_official', true)
            ->orderBy('sort_order')->orderBy('title')->get();
    }

    /**
     * Active sources whose website an admin has verified.
     *
     * @return Collection<int, Source>
     */
    public function verifiedSources(): Collection
    {
        return Source::active()->whereNotNull('website_url')->orderBy('name')->get();
    }

    /**
     * Footer links: legal pages appear only once they have text or a link.
     *
     * @return list<array{label: string, route: string}>
     */
    public function footerLinks(): array
    {
        $links = [
            ['label' => 'عن المنصة', 'route' => 'about'],
            ['label' => 'مصادر رسمية', 'route' => 'resources'],
        ];

        foreach (self::LEGAL_PAGES as $page => $definition) {
            if ($this->settings->get($definition['text']) || $this->settings->get($definition['url'])) {
                $links[] = ['label' => $definition['title'], 'route' => $page];
            }
        }

        return $links;
    }

    /**
     * Plain text split on blank lines. Output is escaped by Blade.
     *
     * @return list<string>
     */
    private function paragraphs(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R\s*\R/u', $text)),
            fn (string $paragraph): bool => $paragraph !== '',
        ));
    }
}
