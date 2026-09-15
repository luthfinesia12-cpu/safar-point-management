<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! $user->is_active || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->audit->record($request, 'login_failed', 'authentication', 'Percobaan login gagal', null, ['email' => $credentials['email']]);

            return back()->withErrors(['email' => 'Email atau password tidak valid.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $this->audit->record($request, 'login', 'authentication', 'Pengguna berhasil login');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->audit->record($request, 'logout', 'authentication', 'Pengguna logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
