<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin') ?? $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Accès refusé. Privilèges administrateur requis.',
            ], 403);
        }

        return $next($request);
    }
}
