<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /** @param  string  ...$roles */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->role === 'Administrator' || in_array($user->role, $roles, true)) {
            return $next($request);
        }
        abort(403, 'Insufficient permissions for this action.');
    }
}
