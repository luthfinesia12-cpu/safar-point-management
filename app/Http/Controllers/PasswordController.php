<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => Hash::make($request->validated('password')), 'must_change_password' => false, 'password_changed_at' => now()]);
        $this->audit->record($request, 'password_changed', 'authentication', 'Pengguna mengubah password sendiri');

        return redirect()->route('dashboard')->with('status', 'Password berhasil diubah.');
    }
}
