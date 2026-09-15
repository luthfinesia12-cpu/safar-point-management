<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->is_active) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Akun Anda tidak aktif.']);
        }

        return $next($request);
    }
}
