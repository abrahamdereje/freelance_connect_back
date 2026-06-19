<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UpdateLastSeen
{
    /**
     * Update the authenticated user's last_seen_at timestamp.
     * Only writes once per minute to avoid DB thrashing.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && (!$user->last_seen_at || $user->last_seen_at->lt(now()->subMinute()))) {
            $user->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
