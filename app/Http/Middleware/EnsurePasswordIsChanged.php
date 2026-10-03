<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the `force_password_change` setting is on, a user whose password
 * was set by an admin (`must_change_password`) is sent to the change
 * password page before they can use anything else.
 */
class EnsurePasswordIsChanged
{
    public function __construct(private readonly SettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && $this->settings->get('force_password_change')) {
            return to_route('password.change');
        }

        return $next($request);
    }
}
