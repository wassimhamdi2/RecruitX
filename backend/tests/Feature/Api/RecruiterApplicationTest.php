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

    private function application(string $status = 'applied'): Application
    {
        $company = Company::create(['name' => 'Test Co']);
        $job = JobOffer::create([
            'company_id' => $company->id,
            'created_by' => $this->recruiterUser()->id,
            'title' => 'Laravel Developer',
            'slug' => 'laravel-developer-'.uniqid(),
            'description' => 'Build APIs.',
            'employment_type' => 'full_time',
            'work_mode' => 'hybrid',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        $candidateProfile = Candidate::create([
            'user_id' => $candidate->id,
            'first_name' => 'Ahmed',
            'last_name' => 'Test',
        ]);

        return $candidateProfile->applications()->create([
            'job_offer_id' => $job->id,
            'status' => $status,
            'applied_at' => now(),
        ]);
    }

    public function test_recruiter_can_list_all_applications(): void
    {
        $this->application();

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->getJson('/api/v1/recruiter/applications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.candidate.name', 'Ahmed Test');
    }

    public function test_recruiter_can_change_status_to_valid_next_state(): void
    {
        $app = $this->application('applied');

        $this->actingAs($this->recruiterUser(), 'sanctum')
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
        $app = $this->application('applied');

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->patchJson("/api/v1/applications/{$app->id}/status", ['status' => 'hired'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('applications', ['id' => $app->id, 'status' => 'applied']);
        $this->assertDatabaseCount('application_status_histories', 0);
    }

    public function test_candidate_cannot_change_status(): void
    {
        $app = $this->application('applied');
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
        $this->application('applied');
        $this->application('hired');

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->getJson('/api/v1/recruiter/applications?status=hired')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'hired');
    }
}