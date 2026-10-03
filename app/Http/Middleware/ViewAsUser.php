<?php

namespace App\Http\Middleware;

use App\Services\ViewAsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies a read-only "view as user" session (see ViewAsService).
 *
 * - Only GET/HEAD requests are served as the viewed user.
 * - Every other request is refused, except ending the view and logging out.
 * - Each viewed request runs inside a database transaction that is always
 *   rolled back, so even a page that records something on open (such as
 *   marking a notification read) leaves no trace.
 */
class ViewAsUser
{
    public function __construct(private readonly ViewAsService $viewAs) {}

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if ($admin === null || ! $request->hasSession() || ! $request->session()->has(ViewAsService::SESSION_KEY)) {
            return $next($request);
        }

        $target = $this->viewAs->target($request->session(), $admin);

        if ($target === null || $request->routeIs('logout')) {
            $request->session()->forget(ViewAsService::SESSION_KEY);

            return $next($request);
        }

        if ($request->routeIs('view-as.stop')) {
            return $next($request);
        }

        if (! $request->isMethodSafe()) {
            return back()->with('info', 'وضع العرض للقراءة فقط؛ لم يُحفظ أي تغيير. أنهي العرض للعودة إلى حسابك.');
        }

        Auth::guard('web')->setUser($target);
        $request->setUserResolver(fn () => $target);
        $request->attributes->set('view_as', ['admin' => $admin, 'target' => $target]);

        DB::beginTransaction();

        try {
            return $next($request);
        } finally {
            DB::rollBack();
            Auth::guard('web')->setUser($admin);
        }
    }
}
