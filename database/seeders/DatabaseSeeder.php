<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view', 'users.view', 'users.create', 'users.update', 'users.deactivate',
            'users.reset-password', 'users.manage-role', 'roles.manage', 'audit.view', 'settings.manage',
            'master-data.manage', 'master-data.import', 'bank-accounts.manage', 'purchase-requests.create', 'purchase-requests.submit', 'purchase-requests.approve', 'purchase-requests.cancel',
            'purchase-orders.create', 'purchase-orders.revise', 'purchase-orders.approve', 'purchase-orders.cancel', 'purchase-orders.close-short', 'goods-receipts.create', 'payments.create', 'payments.verify', 'adjustments.create', 'adjustments.approve', 'attachments.create', 'attachments.replace', 'notifications.resend', 'reports.view',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        foreach (['Admin', 'Keuangan', 'Purchasing', 'Gudang', 'Viewer', 'Owner/Direksi', 'Super Admin', 'Admin Master Data', 'Finance', 'Viewer/Auditor'] as $baseRole) {
            Role::firstOrCreate(['name' => $baseRole, 'guard_name' => 'web']);
        }
        $rolePermissions = [
            'Owner/Direksi' => ['dashboard.view', 'audit.view', 'purchase-requests.create', 'purchase-requests.approve', 'purchase-requests.cancel', 'purchase-orders.create', 'purchase-orders.approve', 'purchase-orders.cancel', 'purchase-orders.close-short', 'goods-receipts.create', 'adjustments.create', 'adjustments.approve', 'payments.create', 'payments.verify', 'attachments.create', 'attachments.replace', 'notifications.resend', 'reports.view'],
            'Admin Master Data' => ['dashboard.view', 'master-data.manage', 'master-data.import', 'reports.view'],
            'Purchasing' => ['dashboard.view', 'purchase-requests.create', 'purchase-requests.submit', 'purchase-requests.cancel', 'purchase-orders.create', 'purchase-orders.revise', 'purchase-orders.close-short', 'reports.view', 'attachments.create'],
            'Gudang' => ['dashboard.view', 'goods-receipts.create', 'adjustments.create', 'reports.view', 'attachments.create'],
            'Finance' => ['dashboard.view', 'payments.create', 'bank-accounts.manage', 'attachments.create', 'attachments.replace', 'notifications.resend', 'reports.view'],
            'Viewer/Auditor' => ['dashboard.view', 'audit.view', 'reports.view'],
        ];
        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            Role::where('name', $roleName)->firstOrFail()->syncPermissions($rolePermissionNames);
        }
        $admin = User::where('email', 'admin@safarpoint.local')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@safarpoint.local',
                'password' => Hash::make('ChangeMe!123'),
                'is_active' => true,
                'must_change_password' => true,
            ]);
            $admin->assignRole($role);
        }
        CompanySetting::firstOrCreate([], ['name' => 'Safar Point']);
    }
}
