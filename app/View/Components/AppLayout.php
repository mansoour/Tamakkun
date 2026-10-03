<?php

namespace App\View\Components;

use App\Services\DashboardRedirector;
use App\Support\Navigation;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Authenticated page shell: header, responsive sidebar/drawer navigation
 * and a centred content column.
 *
 * Pages inside an area pass `area` explicitly. Shared pages such as the
 * profile omit it and get the area the user would land on after login.
 */
class AppLayout extends Component
{
    public string $area;

    public function __construct(?string $area = null, public ?string $title = null)
    {
        $this->area = $area ?? $this->defaultArea();
    }

    public function render(): View
    {
        return view('layouts.app', [
            'navigation' => Navigation::for($this->area),
            'areaTitle' => Navigation::title($this->area),
        ]);
    }

    private function defaultArea(): string
    {
        $user = request()->user();
        $route = $user ? app(DashboardRedirector::class)->routeFor($user) : null;

        return $route ? Str::before($route, '.') : 'student';
    }
}
