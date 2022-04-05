<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Authenticate
{
    private string $integration_id;

    /**
     * Create a new middleware instance.
     *
     * @param Auth $auth
     * @return void
     */
    public function __construct()
    {
        $this->integration_id = app()->has('integration_id') ? app()->get('integration_id') : "";
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  Closure  $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $guard = null) : mixed
    {
        return $next($request);
    }
}
