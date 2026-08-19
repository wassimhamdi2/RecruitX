<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_lists_users(): void
    {
        User::factory()->count(3)->create();
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_admin_changes_user_role(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $user->assignRole('candidate');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$user->id}/role", ['role' => 'recruiter'])
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'recruiter');

        $this->assertTrue($user->fresh()->hasRole('recruiter'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.role_changed', 'user_id' => $admin->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertUnprocessable();
    }

    public function test_admin_updates_company(): void
    {
        $company = Company::create(['name' => 'Old Co']);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/admin/companies/{$company->id}", ['name' => 'New Co', 'city' => 'Paris'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Co');

        $this->assertDatabaseHas('audit_logs', ['action' => 'company.updated']);
    }

    public function test_non_admin_cannot_access_admin_endpoints(): void
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');

        $this->actingAs($recruiter, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();
    }

    public function test_audit_logs_recorded_and_listable_by_admin(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $user->assignRole('candidate');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$user->id}/role", ['role' => 'recruiter'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/audit-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'user.role_changed');
    }

    public function test_login_is_audited(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);
        $user->assignRole('candidate');

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.login', 'user_id' => $user->id]);
    }
}