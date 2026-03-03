<?php

namespace App\Http\Middleware;

use App\Models\SiteMember;
use App\Models\SiteWorker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForemanMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $isForemanByRole = $user->role === 'foreman';
        $isForemanBySiteWorker = SiteWorker::where('user_id', $user->id)
            ->where('is_foreman', true)
            ->whereNull('ended_at')
            ->exists();
        $isForemanByMembership = SiteMember::where('user_id', $user->id)
            ->where('role', 'foreman')
            ->exists();

        if (!$isForemanByRole && !$isForemanBySiteWorker && !$isForemanByMembership) {
            abort(403, 'Foreman access required.');
        }

        return $next($request);
    }
}
