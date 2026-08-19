<?php

namespace Tests\Feature\Api;

use App\Enums\JobStatus;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function candidateUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('candidate');
        Candidate::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Candidate',
        ]);

        return $user;
    }

    private function job(JobStatus $status = JobStatus::PUBLISHED): JobOffer
    {
        $company = Company::create(['name' => 'Test Co']);

        return JobOffer::create([
            'company_id' => $company->id,
            'created_by' => User::factory()->create()->id,
            'title' => 'Laravel Developer',
            'slug' => 'laravel-developer-'.uniqid(),
            'description' => 'Build APIs.',
            'employment_type' => 'full_time',
            'work_mode' => 'hybrid',
            'status' => $status,
            'published_at' => now(),
        ]);
    }

    public function test_candidate_can_apply_to_published_job(): void
    {
        $user = $this->candidateUser();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/jobs/{$this->job()->id}/apply")
            ->assertCreated()
            ->assertJsonPath('data.status', 'applied');

        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('application_status_histories', 1);
    }

    public function test_candidate_cannot_apply_twice_to_same_job(): void
    {
        $user = $this->candidateUser();
        $job = $this->job();

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/jobs/{$job->id}/apply")->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/jobs/{$job->id}/apply")
            ->assertStatus(422)
            ->assertJsonValidationErrors('job');

        $this->assertDatabaseCount('applications', 1);
    }

    public function test_candidate_cannot_apply_to_closed_job(): void
    {
        $user = $this->candidateUser();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/jobs/{$this->job(JobStatus::CLOSED)->id}/apply")
            ->assertStatus(422);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_candidate_without_profile_cannot_apply(): void
    {
        $user = User::factory()->create();
        $user->assignRole('candidate');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/jobs/{$this->job()->id}/apply")
            ->assertStatus(422);
    }

    public function test_candidate_sees_only_own_applications(): void
    {
        $user = $this->candidateUser();
        $job = $this->job();
        $other = User::factory()->create();
        $other->assignRole('candidate');
        $otherCandidate = Candidate::create([
            'user_id' => $other->id,
            'first_name' => 'Other',
            'last_name' => 'Candidate',
        ]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/jobs/{$job->id}/apply");
        $otherCandidate->applications()->create([
            'job_offer_id' => $this->job()->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/applications');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_jobs_list_returns_only_published(): void
    {
        $this->job();
        $this->job(JobStatus::DRAFT);

        $this->actingAs($this->candidateUser(), 'sanctum')
            ->getJson('/api/v1/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}