<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        // Blade + Vite same-origin. Inline styles allowed for utility/focus; scripts stay self-only.
        $scriptSrc = "script-src 'self'";
        $connectSrc = "connect-src 'self'";

        if (app()->environment('local')) {
            // Vite HMR during local development only.
            $scriptSrc = "script-src 'self' 'unsafe-eval'";
            $connectSrc = "connect-src 'self' ws: wss: http://127.0.0.1:* http://localhost:*";
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            $scriptSrc,
            $connectSrc,
            "object-src 'none'",
        ]);
    }
}
