<?php

namespace App\View\Components;

use App\Services\PublicPageService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Lightweight shell for the public pages: header, content and a footer
 * that links only to public pages that actually have content.
 */
class PublicLayout extends Component
{
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        $pages = app(PublicPageService::class);

        return view('layouts.public', ['navLinks' => $pages->navLinks(), 'footerLinks' => $pages->footerLinks()]);
    }
}
