<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('password.change', 'password.update', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
