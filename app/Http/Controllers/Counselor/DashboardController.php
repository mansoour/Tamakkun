<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetricsService $metrics): View
    {
        return view('counselor.dashboard', ['metrics' => $metrics->counselorOverview($request->user())]);
    }
}
