<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOfferCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function recruiter(): User
    {
        $user = User::factory()->create();
        $user->assignRole('recruiter');

        return $user;
    }

    private function candidate(): User
    {
        $user = User::factory()->create();
        $user->assignRole('candidate');

        return $user;
    }

    private function company(): Company
    {
        return Company::create(['name' => 'Acme Corp']);
    }

    private function payload(Company $company, array $overrides = []): array
    {
        return array_merge([
            'company_id' => $company->id,
            'title' => 'Backend Engineer',
            'description' => 'Build great APIs.',
            'employment_type' => 'full_time',
            'work_mode' => 'hybrid',
            'location' => 'Casablanca',
            'salary_min' => 1000,
            'salary_max' => 2000,
            'status' => 'published',
        ], $overrides);
    }

    public function test_recruiter_can_create_job(): void
    {
        $skill = Skill::create(['name' => 'PHP']);
        $recruiter = $this->recruiter();

        $response = $this->actingAs($recruiter, 'sanctum')
            ->postJson('/api/v1/jobs', $this->payload($this->company(), ['skills' => [$skill->id]]))
            ->assertCreated()
            ->assertJsonPath('data.title', 'Backend Engineer');

        $this->assertDatabaseHas('job_offers', ['id' => $response->json('data.id'), 'created_by' => $recruiter->id, 'status' => 'published']);
        $this->assertDatabaseHas('job_offer_skills', ['job_offer_id' => $response->json('data.id'), 'skill_id' => $skill->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'job.created']);
    }

    public function test_job_slug_is_unique(): void
    {
        $recruiter = $this->recruiter();
        $company = $this->company();

        $this->actingAs($recruiter, 'sanctum')->postJson('/api/v1/jobs', $this->payload($company))->assertCreated();
        $this->actingAs($recruiter, 'sanctum')->postJson('/api/v1/jobs', $this->payload($company))->assertCreated();

        $this->assertNotSame(
            JobOffer::where('title', 'Backend Engineer')->first()->slug,
            JobOffer::where('title', 'Backend Engineer')->skip(1)->first()->slug,
        );
    }

    public function test_validation_errors(): void
    {
        $recruiter = $this->recruiter();

        $this->actingAs($recruiter, 'sanctum')
            ->postJson('/api/v1/jobs', ['title' => 'No Company'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id', 'description', 'employment_type', 'work_mode']);
    }

    public function test_candidate_cannot_create_job(): void
    {
        $this->actingAs($this->candidate(), 'sanctum')
            ->postJson('/api/v1/jobs', $this->payload($this->company()))
            ->assertForbidden();
    }

    public function test_recruiter_can_update_and_list_own_jobs(): void
    {
        $recruiter = $this->recruiter();
        $job = $this->createJob($recruiter);

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/jobs/{$job->id}", ['salary_max' => 5000, 'status' => 'draft'])
            ->assertOk()
            ->assertJsonPath('data.salary_max', '5000.00');

        $this->actingAs($recruiter, 'sanctum')
            ->getJson('/api/v1/recruiter/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_recruiter_cannot_edit_another_recruiters_job(): void
    {
        $recruiterA = $this->recruiter();
        $recruiterB = $this->recruiter();
        $job = $this->createJob($recruiterA);

        $this->actingAs($recruiterB, 'sanctum')
            ->patchJson("/api/v1/jobs/{$job->id}", ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($recruiterB, 'sanctum')
            ->deleteJson("/api/v1/jobs/{$job->id}")
            ->assertForbidden();
    }

    public function test_admin_can_delete_any_job(): void
    {
        $recruiter = $this->recruiter();
        $job = $this->createJob($recruiter);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/jobs/{$job->id}")
            ->assertOk();

        $this->assertDatabaseMissing('job_offers', ['id' => $job->id]);
    }

    private function createJob(User $recruiter): JobOffer
    {
        return JobOffer::create([
            'company_id' => $this->company()->id,
            'created_by' => $recruiter->id,
            'title' => 'Test Job',
            'slug' => 'test-job-'.uniqid(),
            'description' => 'Desc',
            'employment_type' => 'full_time',
            'work_mode' => 'on_site',
            'status' => 'published',
        ]);
    }
}