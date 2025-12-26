<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuper
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isSuper()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Super access required.'], 403);
            }

            abort(403, 'Forbidden. Super access required.');
        }

        return $next($request);
    }
}
