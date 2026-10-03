<?php

namespace App\Http\Controllers;

use App\Services\PublicPageService;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function __construct(private readonly PublicPageService $pages) {}

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

    public function privacy(): View
    {
        return view('public.legal', ['page' => $this->pages->legalPage('privacy')]);
    }

    public function terms(): View
    {
        return view('public.legal', ['page' => $this->pages->legalPage('terms')]);
    }
}
