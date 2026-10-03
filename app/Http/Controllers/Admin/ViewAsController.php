<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardRedirector;
use App\Services\ViewAsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ViewAsController extends Controller
{
    public function __construct(private readonly ViewAsService $viewAs) {}

    public function start(Request $request, User $user, DashboardRedirector $redirector): RedirectResponse
    {
        try {
            $this->viewAs->start($request->session(), $request->user(), $user);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['view_as' => $e->getMessage()]);
        }

        $route = $redirector->routeFor($user);

        return $route ? redirect()->route($route) : redirect()->route('profile.edit');
    }

    public function stop(Request $request): RedirectResponse
    {
        $this->viewAs->stop($request->session());

        return redirect()->route('admin.dashboard')->with('success', 'انتهى وضع العرض وعدتِ إلى حسابك.');
    }
}
