<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function recruiterUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('recruiter');

        return $user;
    }

    private function jobFor(User $recruiter, string $title = 'Laravel Developer'): JobOffer
    {
        return JobOffer::create([
            'company_id' => Company::create(['name' => 'Test Co'])->id,
            'created_by' => $recruiter->id,
            'title' => $title,
            'slug' => strtolower(str_replace(' ', '-', $title)).'-'.uniqid(),
            'description' => 'Build APIs.',
            'employment_type' => 'full_time',
            'work_mode' => 'hybrid',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function applicationFor(User $recruiter, string $status = 'applied'): Application
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        $profile = Candidate::create(['user_id' => $candidate->id, 'first_name' => 'Ahmed', 'last_name' => 'Test']);

        return $profile->applications()->create([
            'job_offer_id' => $this->jobFor($recruiter)->id,
            'status' => $status,
            'applied_at' => now(),
        ]);
    }

    public function test_recruiter_sees_only_own_jobs_applications(): void
    {
        $recruiterA = $this->recruiterUser();
        $recruiterB = $this->recruiterUser();
        $this->applicationFor($recruiterA);
        $this->applicationFor($recruiterB);

        $this->actingAs($recruiterA, 'sanctum')
            ->getJson('/api/v1/recruiter/applications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_history_shows_status_changes(): void
    {
        $recruiter = $this->recruiterUser();
        $application = $this->applicationFor($recruiter);

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/applications/{$application->id}/status", ['status' => 'screening'])
            ->assertOk();

        $this->actingAs($recruiter, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}/history")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.to_status', 'screening')
            ->assertJsonPath('data.0.changed_by', $recruiter->name);
    }

    public function test_candidate_can_see_own_application_history(): void
    {
        $recruiter = $this->recruiterUser();
        $candidateUser = User::factory()->create();
        $candidateUser->assignRole('candidate');
        $profile = Candidate::create(['user_id' => $candidateUser->id, 'first_name' => 'T', 'last_name' => 'C']);
        $application = $profile->applications()->create([
            'job_offer_id' => $this->jobFor($recruiter)->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/applications/{$application->id}/status", ['status' => 'screening'])
            ->assertOk();

        $this->actingAs($candidateUser, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}/history")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $other = User::factory()->create();
        $other->assignRole('candidate');
        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}/history")
            ->assertForbidden();
    }

    public function test_recruiter_can_list_all_applications(): void
    {
        $recruiter = $this->recruiterUser();
        $this->applicationFor($recruiter);

        $this->actingAs($recruiter, 'sanctum')
            ->getJson('/api/v1/recruiter/applications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.candidate.name', 'Ahmed Test');
    }

    public function test_recruiter_can_change_status_to_valid_next_state(): void
    {
        $recruiter = $this->recruiterUser();
        $app = $this->applicationFor($recruiter);

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/applications/{$app->id}/status", ['status' => 'screening', 'comment' => 'CV review'])
            ->assertOk()
            ->assertJsonPath('data.status', 'screening');

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $app->id,
            'from_status' => 'applied',
            'to_status' => 'screening',
            'comment' => 'CV review',
        ]);
    }

    public function test_recruiter_cannot_make_illegal_transition(): void
    {
        $recruiter = $this->recruiterUser();
        $app = $this->applicationFor($recruiter);

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/applications/{$app->id}/status", ['status' => 'hired'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('applications', ['id' => $app->id, 'status' => 'applied']);
        $this->assertDatabaseCount('application_status_histories', 0);
    }

    public function test_candidate_cannot_change_status(): void
    {
        $recruiter = $this->recruiterUser();
        $app = $this->applicationFor($recruiter);
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->actingAs($candidate, 'sanctum')
            ->patchJson("/api/v1/applications/{$app->id}/status", ['status' => 'screening'])
            ->assertStatus(403);

        $this->assertDatabaseHas('applications', ['id' => $app->id, 'status' => 'applied']);
    }

    public function test_candidate_cannot_access_recruiter_list(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/recruiter/applications')
            ->assertStatus(403);
    }

    public function test_recruiter_list_can_filter_by_status(): void
    {
        $recruiter = $this->recruiterUser();
        $this->applicationFor($recruiter, 'applied');
        $this->applicationFor($recruiter, 'hired');

        $this->actingAs($recruiter, 'sanctum')
            ->getJson('/api/v1/recruiter/applications?status=hired')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'hired');
    }
}