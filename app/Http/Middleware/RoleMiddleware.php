<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,viewer')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated. Please login.'], 401);
        }

        $userRole = $request->user()->role ?? 'viewer';

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'error' => 'Forbidden. Insufficient privileges.',
                'required' => $roles,
                'current' => $userRole,
            ], 403);
        }

        return $next($request);
    }
}
