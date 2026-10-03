<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetricsService $metrics): View
    {
        return view('admin.dashboard', ['metrics' => $metrics->adminOverview()]);
    }
}
