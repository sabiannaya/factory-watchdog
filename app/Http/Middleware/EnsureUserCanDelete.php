<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanDelete
{
    /**
     * Handle an incoming request.
     * Only Super users can delete resources.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canDelete()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Only super users can delete resources.'], 403);
            }

            abort(403, 'Forbidden. Only super users can delete resources.');
        }

        return $next($request);
    }
}
