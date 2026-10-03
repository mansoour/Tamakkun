<?php

namespace App\Http\Middleware;

use App\Support\VideoEmbed;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline security headers to every web response.
 * See docs/security.md for the reasoning behind each header.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The Vite dev server injects scripts from another origin, so no
        // policy is sent while `npm run dev` is running. Locally the policy
        // is report-only because Laravel Boost injects an inline logging
        // script; violations still show up in the browser console.
        if (! Vite::isRunningHot()) {
            $header = app()->environment('local')
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $headers->set($header, $this->contentSecurityPolicy());
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            // Alpine.js evaluates directive expressions, which requires 'unsafe-eval'.
            "script-src 'self' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data:",
            "connect-src 'self'",
            // Only whitelisted video players may be embedded (App\Support\VideoEmbed).
            'frame-src '.implode(' ', VideoEmbed::FRAME_HOSTS),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
