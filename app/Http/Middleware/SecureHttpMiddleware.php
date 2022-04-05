<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use Closure;
use Illuminate\Http\Request;

class SecureHttpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }
}
