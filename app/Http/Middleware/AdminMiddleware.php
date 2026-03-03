<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || $request->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized - Admin access required');
        }

        return $next($request);
    }
}
