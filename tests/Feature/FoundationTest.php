<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_requires_password_change_and_then_opens_dashboard(): void
    {
        $response = $this->followingRedirects()->post(route('login.store'), ['email' => 'admin@safarpoint.local', 'password' => 'ChangeMe!123']);
        $response->assertOk();
        $this->assertAuthenticated();

        $this->put(route('password.update'), ['password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'login', 'module' => 'authentication']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_changed']);
    }

    public function test_failed_login_is_audited_without_password_data(): void
    {
        $this->post(route('login.store'), ['email' => 'admin@safarpoint.local', 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $log = AuditLog::where('action', 'login_failed')->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('wrong-password', json_encode($log->toArray()));
    }

    public function test_non_admin_cannot_access_users_and_admin_can_manage_without_delete(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();

        $admin = User::where('email', 'admin@safarpoint.local')->firstOrFail();
        $admin->update(['must_change_password' => false]);
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Operator', 'email' => 'operator@safarpoint.local', 'password' => 'Operator!123', 'password_confirmation' => 'Operator!123', 'role' => 'Super Admin',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'operator@safarpoint.local']);
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function test_company_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $admin = User::where('email', 'admin@safarpoint.local')->firstOrFail();
        $admin->update(['must_change_password' => false]);
        $this->actingAs($admin)->put(route('settings.company.update'), [
            'name' => 'Safar Point', 'email' => 'hello@safarpoint.local', 'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertSessionHasNoErrors();
        $setting = CompanySetting::firstOrFail();
        Storage::disk('public')->assertExists($setting->logo_path);
        $this->actingAs($admin)->put(route('settings.company.update'), ['name' => 'Safar Point', 'remove_logo' => 1])->assertSessionHasNoErrors();
        $this->assertNull($setting->fresh()->logo_path);
    }

    public function test_super_admin_protections_and_base_roles_are_enforced(): void
    {
        $admin = User::where('email', 'admin@safarpoint.local')->firstOrFail()->forceFill(['must_change_password' => false]);
        $admin->save();
        foreach (['Admin', 'Keuangan', 'Purchasing', 'Gudang', 'Viewer'] as $role) {
            $this->assertTrue(Role::where('name', $role)->exists());
        }
        $this->actingAs($admin)->patch(route('users.toggle', $admin))->assertStatus(422);
        $this->actingAs($admin)->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'Viewer'])->assertStatus(422);
        $this->actingAs($admin)->delete(route('roles.destroy', Role::where('name', 'Super Admin')->first()))->assertStatus(422);
        $this->assertTrue($admin->fresh()->hasRole('Super Admin'));
    }

    public function test_last_active_super_admin_cannot_be_deactivated(): void
    {
        $admin = User::where('email', 'admin@safarpoint.local')->firstOrFail()->forceFill(['must_change_password' => false]);
        $admin->save();
        $second = User::factory()->create(['must_change_password' => false]);
        $second->assignRole('Super Admin');
        $this->actingAs($admin)->patch(route('users.toggle', $second))->assertRedirect();
        $this->assertFalse($second->fresh()->is_active);
        $this->actingAs($admin)->patch(route('users.toggle', $admin))->assertStatus(422);
    }

    public function test_reset_password_requires_confirmation_and_minimum_twelve_characters(): void
    {
        $admin = User::where('email', 'admin@safarpoint.local')->firstOrFail()->forceFill(['must_change_password' => false]);
        $admin->save();
        $target = User::factory()->create();
        $response = $this->actingAs($admin)->post(route('users.reset-password', $target), ['password' => 'short', 'password_confirmation' => 'short']);
        $response->assertSessionHasErrors(['confirmed_action', 'password']);
        $this->assertStringContainsString('minimal 12 karakter', session('errors')->first('password'));
    }

    public function test_user_and_audit_filters_work(): void
    {
        $admin = User::where('email', 'admin@safarpoint.local')->firstOrFail()->forceFill(['must_change_password' => false]);
        $admin->save();
        $target = User::factory()->create(['name' => 'Filter Target', 'is_active' => false]);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'filter_action', 'module' => 'filter_module', 'description' => 'Filter test', 'ip_address' => '127.0.0.1', 'created_at' => now()]);
        $this->actingAs($admin)->get(route('users.index', ['search' => 'Filter Target', 'status' => 'inactive']))->assertSee('Filter Target');
        $this->actingAs($admin)->get(route('audit.index', ['user_id' => $admin->id, 'module' => 'filter_module', 'action' => 'filter_action']))->assertSee('Filter test');
        $this->actingAs($admin)->get(route('audit.show', AuditLog::latest('id')->first()))->assertSee('Detail Audit');
    }

    public function test_database_seeder_preserves_changed_super_admin_credentials_on_repeated_runs(): void
    {
        $admin = User::where('email', 'admin@safarpoint.local')->firstOrFail();
        $admin->forceFill(['password' => Hash::make('ChangedPassword!123'), 'must_change_password' => false, 'is_active' => false])->save();
        $passwordHash = $admin->fresh()->password;
        $mustChangePassword = $admin->fresh()->must_change_password;
        $this->seed();
        $this->seed();
        $admin->refresh();
        $this->assertSame($passwordHash, $admin->password);
        $this->assertSame($mustChangePassword, $admin->must_change_password);
        $this->assertFalse($admin->is_active);
        $this->assertSame('Super Admin', $admin->name);
    }
}
