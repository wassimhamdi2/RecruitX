<?php

namespace Tests\Feature\Api;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EvaluationCriterion;
use App\Models\Interview;
use App\Models\JobOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->tech = EvaluationCriterion::create(['name' => 'Technical Skills', 'max_score' => 10, 'weight' => 3]);
        $this->comm = EvaluationCriterion::create(['name' => 'Communication', 'max_score' => 10, 'weight' => 2]);
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

    private function recruiterUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('recruiter');

        return $user;
    }

    private function interview(): array
    {
        $company = Company::create(['name' => 'Test Co']);
        $job = JobOffer::create([
            'company_id' => $company->id,
            'created_by' => User::factory()->create()->id,
            'title' => 'Laravel Developer',
            'slug' => 'laravel-developer-'.uniqid(),
            'description' => 'Build APIs.',
            'employment_type' => 'full_time',
            'work_mode' => 'hybrid',
            'status' => JobStatus::PUBLISHED,
            'published_at' => now(),
        ]);
        $application = Application::create([
            'job_offer_id' => $job->id,
            'candidate_id' => $this->candidateUser()->candidate->id,
            'status' => 'interview',
            'applied_at' => now(),
        ]);
        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_by' => $this->recruiterUser()->id,
            'type' => 'technical',
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        return [$application, $interview];
    }

    public function test_recruiter_can_evaluate_interview(): void
    {
        [$application, $interview] = $this->interview();

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews/{$interview->id}/evaluation", [
                'recommendation' => 'yes',
                'comments' => 'Strong technical profile.',
                'scores' => [
                    ['criterion_id' => $this->tech->id, 'score' => 8],
                    ['criterion_id' => $this->comm->id, 'score' => 6],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.recommendation', 'yes')
            ->assertJsonPath('data.overall_score', '7.20')
            ->assertJsonPath('data.candidate.name', 'Test Candidate');

        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseCount('evaluation_scores', 2);
    }

    public function test_score_cannot_exceed_max_score(): void
    {
        [$application, $interview] = $this->interview();

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews/{$interview->id}/evaluation", [
                'recommendation' => 'yes',
                'scores' => [
                    ['criterion_id' => $this->tech->id, 'score' => 11],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_unknown_criterion_is_rejected(): void
    {
        [$application, $interview] = $this->interview();

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews/{$interview->id}/evaluation", [
                'recommendation' => 'yes',
                'scores' => [
                    ['criterion_id' => 9999, 'score' => 5],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_candidate_cannot_create_evaluation(): void
    {
        [$application, $interview] = $this->interview();

        $this->actingAs($this->candidateUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews/{$interview->id}/evaluation", [
                'recommendation' => 'yes',
                'scores' => [['criterion_id' => $this->tech->id, 'score' => 5]],
            ])
            ->assertForbidden();
    }

    public function test_evaluation_requires_interview_of_application(): void
    {
        [$application, $interview] = $this->interview();
        [$otherApplication] = $this->interview();

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$otherApplication->id}/interviews/{$interview->id}/evaluation", [
                'recommendation' => 'yes',
                'scores' => [['criterion_id' => $this->tech->id, 'score' => 5]],
            ])
            ->assertStatus(422);
    }

    public function test_recruiter_can_list_evaluations(): void
    {
        [$application, $interview] = $this->interview();
        $recruiter = $this->recruiterUser();

        $this->actingAs($recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews/{$interview->id}/evaluation", [
                'recommendation' => 'yes',
                'scores' => [['criterion_id' => $this->tech->id, 'score' => 8]],
            ])->assertCreated();

        $this->actingAs($recruiter, 'sanctum')
            ->getJson('/api/v1/recruiter/evaluations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.scores.0.criterion', 'Technical Skills');
    }

    public function test_candidate_cannot_access_evaluations(): void
    {
        $this->actingAs($this->candidateUser(), 'sanctum')
            ->getJson('/api/v1/recruiter/evaluations')
            ->assertForbidden();
    }
}