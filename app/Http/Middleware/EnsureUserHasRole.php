<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(
            fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        if ($user === null || ! $user->hasRole(...$allowed)) {
            abort(403);
        }

        return $next($request);
    }
}
