<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'companies.view', 'companies.create', 'companies.update', 'companies.delete',
            'jobs.view', 'jobs.create', 'jobs.update', 'jobs.delete',
            'candidates.view', 'candidates.create', 'candidates.update', 'candidates.delete',
            'applications.view', 'applications.create', 'applications.update', 'applications.delete',
            'applications.view-own',
            'interviews.view', 'interviews.create', 'interviews.update',
            'interviews.view-own',
            'evaluations.view', 'evaluations.create', 'evaluations.update',
            'reports.view',
            'profile.update',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $recruiter = Role::findOrCreate('recruiter');
        $recruiter->syncPermissions([
            'companies.view',
            'jobs.view', 'jobs.create', 'jobs.update',
            'candidates.view',
            'applications.view', 'applications.update',
            'interviews.view', 'interviews.create', 'interviews.update',
            'evaluations.view', 'evaluations.create', 'evaluations.update',
            'reports.view',
            'profile.update',
        ]);

        $candidate = Role::findOrCreate('candidate');
        $candidate->syncPermissions([
            'jobs.view',
            'applications.create',
            'applications.view-own',
            'interviews.view-own',
            'profile.update',
        ]);
    }
}