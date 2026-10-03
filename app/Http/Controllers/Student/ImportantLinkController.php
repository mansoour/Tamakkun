<?php

namespace App\Http\Controllers\Student;

use App\Enums\LinkCategory;
use App\Http\Controllers\Controller;
use App\Models\ImportantLink;
use Illuminate\View\View;

class ImportantLinkController extends Controller
{
    public function __invoke(): View
    {
        $links = ImportantLink::where('is_active', true)->orderBy('sort_order')->orderBy('title')->get();

        return view('student.links', [
            'groups' => collect(LinkCategory::cases())
                ->map(fn (LinkCategory $category) => ['category' => $category, 'links' => $links->where('category', $category)->values()])
                ->filter(fn ($group) => $group['links']->isNotEmpty())
                ->values(),
        ]);
    }
}
