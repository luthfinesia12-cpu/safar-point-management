<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        return view('roles.index', ['roles' => Role::with('permissions')->orderBy('name')->get(), 'permissions' => Permission::orderBy('name')->get()]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:roles,name'], 'permissions' => ['array'], 'permissions.*' => ['exists:permissions,name']]);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        $audit->record($request, 'role_created', 'roles', 'Role baru dibuat', null, $role->load('permissions')->toArray());

        return back()->with('status', 'Role berhasil dibuat.');
    }

    public function update(Request $request, Role $role, AuditService $audit): RedirectResponse
    {
        abort_if($role->name === 'Super Admin', 422, 'Role Super Admin terkunci.');
        $data = $request->validate(['permissions' => ['array'], 'permissions.*' => ['exists:permissions,name']]);
        $before = $role->load('permissions')->toArray();
        $role->syncPermissions($data['permissions'] ?? []);
        $audit->record($request, 'role_permissions_updated', 'roles', 'Permission role diperbarui', $before, $role->fresh()->load('permissions')->toArray());

        return back()->with('status', 'Permission role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === 'Super Admin', 422, 'Role Super Admin terkunci.');
        $role->delete();

        return back()->with('status', 'Role berhasil dihapus.');
    }
}
