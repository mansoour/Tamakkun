<?php

namespace App\Http\Controllers;

use App\Services\DashboardRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request, DashboardRedirector $redirector): RedirectResponse
    {
        $route = $redirector->routeFor($request->user());

        abort_if($route === null, 403, 'لا توجد صلاحية للدخول إلى أي لوحة. يرجى التواصل مع إدارة المدرسة.');

        return redirect()->route($route);
    }
}
