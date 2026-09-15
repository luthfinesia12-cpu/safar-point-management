<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $users = User::with('roles')->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('email', 'like', '%'.$request->search.'%')))->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))->when($request->filled('role'), fn ($q) => $q->role($request->role))->latest()->paginate(15)->withQueryString();

        return view('users.index', ['users' => $users, 'roles' => Role::orderBy('name')->get(), 'filters' => $request->only('search', 'status', 'role')]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);
        $user = User::create($data);
        $user->syncRoles([$role]);
        $this->audit->record($request, 'user_created', 'users', 'Pengguna baru ditambahkan', null, $user->load('roles')->toArray());

        return back()->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $requestedRole = $request->input('role');
        if ($user->hasRole('Super Admin') && $requestedRole !== 'Super Admin' && User::role('Super Admin')->where('is_active', true)->count() <= 1) {
            abort(422, 'Role Super Admin terakhir yang aktif tidak boleh dihapus.');
        }
        $this->authorize('update', $user);
        $before = $user->load('roles')->toArray();
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);
        if (blank($data['password'])) {
            unset($data['password'], $data['password_confirmation']);
        }
        $user->update($data);
        $user->syncRoles([$role]);
        $after = $user->fresh()->load('roles')->toArray();
        $action = collect($before['roles'] ?? [])->pluck('name')->sort()->values()->all() !== collect($after['roles'] ?? [])->pluck('name')->sort()->values()->all() ? 'role_changed' : 'user_updated';
        $this->audit->record($request, $action, 'users', $action === 'role_changed' ? 'Role pengguna diubah' : 'Data pengguna diperbarui', $before, $after);

        return back()->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Anda tidak dapat menonaktifkan akun sendiri.');
        abort_if($user->hasRole('Super Admin') && $user->is_active && User::role('Super Admin')->where('is_active', true)->count() <= 1, 422, 'Super Admin aktif terakhir tidak dapat dinonaktifkan.');
        $this->authorize('deactivate', $user);
        $before = $user->toArray();
        $user->update(['is_active' => ! $user->is_active]);
        $this->audit->record($request, $user->is_active ? 'user_activated' : 'user_deactivated', 'users', 'Status pengguna diubah', $before, $user->toArray());

        return back()->with('status', 'Status pengguna berhasil diubah.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);
        $request->validate(['confirmed_action' => ['accepted'], 'password' => ['required', 'confirmed', Password::min(12)]]);
        $data = $request->all();
        $user->update(['password' => Hash::make($data['password']), 'must_change_password' => true, 'password_changed_at' => null]);
        $this->audit->record($request, 'password_reset', 'users', 'Password pengguna direset oleh administrator', null, ['user_id' => $user->id]);

        return back()->with('status', 'Password berhasil direset. Pengguna wajib menggantinya saat login.');
    }
}
