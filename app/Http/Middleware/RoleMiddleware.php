<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
                'errors' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is suspended.',
                'data' => null,
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        if (!in_array($user->role->value, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You do not have the required role to access this resource.',
                'data' => null,
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
