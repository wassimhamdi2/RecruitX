<?php

namespace Tests\Feature\Api;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function candidateUser(): User
    {
        $user = $this->userWithRole('candidate');
        Candidate::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Candidate',
        ]);

        return $user;
    }

    private function job(User $recruiter, string $title = 'Laravel Dev'): JobOffer
    {
        return JobOffer::create([
            'company_id' => Company::create(['name' => 'Test Co'])->id,
            'created_by' => $recruiter->id,
            'title' => $title,
            'slug' => strtolower(str_replace(' ', '-', $title)).'-'.uniqid(),
            'description' => 'Build APIs.',
            'employment_type' => 'full_time',
            'work_mode' => 'hybrid',
            'status' => JobStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function test_admin_sees_all_stats(): void
    {
        $recruiter = $this->userWithRole('recruiter');
        $job = $this->job($recruiter);
        $candidate = $this->candidateUser();
        Application::create([
            'job_offer_id' => $job->id,
            'candidate_id' => $candidate->candidate->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $admin = $this->userWithRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/staff/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.jobs', 1)
            ->assertJsonPath('data.totals.applications', 1)
            ->assertJsonPath('data.applications_by_status.applied', 1);
    }

    public function test_recruiter_sees_only_own_jobs(): void
    {
        $recruiterA = $this->userWithRole('recruiter');
        $recruiterB = $this->userWithRole('recruiter');
        $jobA = $this->job($recruiterA, 'Job A');
        $jobB = $this->job($recruiterB, 'Job B');
        $candidate = $this->candidateUser();
        Application::create([
            'job_offer_id' => $jobA->id,
            'candidate_id' => $candidate->candidate->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);
        Application::create([
            'job_offer_id' => $jobB->id,
            'candidate_id' => $candidate->candidate->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $this->actingAs($recruiterA, 'sanctum')
            ->getJson('/api/v1/staff/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.jobs', 1)
            ->assertJsonPath('data.totals.applications', 1)
            ->assertJsonPath('data.top_jobs.0.title', 'Job A');
    }

    public function test_candidate_cannot_access_dashboard(): void
    {
        $this->actingAs($this->candidateUser(), 'sanctum')
            ->getJson('/api/v1/staff/dashboard')
            ->assertForbidden();
    }
}