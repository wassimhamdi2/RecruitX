<?php

namespace Tests\Feature\Api;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\User;
use App\Notifications\InterviewScheduled;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InterviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Notification::fake();
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

    private function application(User $candidate = null): Application
    {
        $candidate ??= $this->candidateUser();
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

        return Application::create([
            'job_offer_id' => $job->id,
            'candidate_id' => $candidate->candidate->id,
            'status' => 'screening',
            'applied_at' => now(),
        ]);
    }

    public function test_recruiter_can_schedule_interview(): void
    {
        $application = $this->application();

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.candidate.name', 'Test Candidate');

        $this->assertDatabaseCount('interviews', 1);
        $this->assertDatabaseCount('interview_participants', 1);
    }

    public function test_interview_requires_future_scheduled_at(): void
    {
        $application = $this->application();

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'phone',
                'scheduled_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_at');
    }

    public function test_candidate_cannot_schedule_interview(): void
    {
        $application = $this->application();

        $this->actingAs($this->candidateUser(), 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertForbidden();
    }

    public function test_candidate_sees_only_own_interviews(): void
    {
        $mine = $this->candidateUser();
        $mineApp = $this->application($mine);
        $other = $this->application();

        $recruiter = $this->recruiterUser();
        foreach ([$mineApp, $other] as $app) {
            $this->actingAs($recruiter, 'sanctum')
                ->postJson("/api/v1/applications/{$app->id}/interviews", [
                    'type' => 'video',
                    'scheduled_at' => now()->addDay()->toDateTimeString(),
                ])->assertCreated();
        }

        $response = $this->actingAs($mine, 'sanctum')->getJson('/api/v1/me/interviews');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_recruiter_can_list_and_filter_interviews(): void
    {
        $application = $this->application();
        $recruiter = $this->recruiterUser();
        $this->actingAs($recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])->assertCreated();

        $this->actingAs($recruiter, 'sanctum')
            ->getJson('/api/v1/recruiter/interviews')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_recruiter_can_update_status(): void
    {
        $application = $this->application();
        $recruiter = $this->recruiterUser();
        $this->actingAs($recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])->assertCreated();

        $interviewId = Application::find($application->id)->interviews->first()->id;

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/interviews/{$interviewId}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_recruiter_cannot_make_illegal_transition(): void
    {
        $application = $this->application();
        $recruiter = $this->recruiterUser();
        $this->actingAs($recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])->assertCreated();

        $interviewId = Application::find($application->id)->interviews->first()->id;

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/interviews/{$interviewId}", ['status' => 'scheduled'])
            ->assertStatus(422);
    }

    public function test_recruiter_can_reschedule_interview(): void
    {
        $application = $this->application();
        $recruiter = $this->recruiterUser();
        $this->actingAs($recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])->assertCreated();

        $interviewId = Application::find($application->id)->interviews->first()->id;
        $newTime = now()->addDays(2)->toDateTimeString();

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/interviews/{$interviewId}", [
                'status' => 'rescheduled',
                'scheduled_at' => $newTime,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rescheduled')
            ->assertJsonPath('data.scheduled_at', \Illuminate\Support\Carbon::parse($newTime)->toJSON());
    }

    public function test_reschedule_notifies_candidate(): void
    {
        $recruiter = $this->recruiterUser();
        $application = $this->application();
        $this->actingAs($recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])->assertCreated();

        $candidate = Application::find($application->id)->candidate->user;
        $interviewId = Application::find($application->id)->interviews->first()->id;

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/interviews/{$interviewId}", [
                'status' => 'rescheduled',
                'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            ])->assertOk();

        Notification::assertSentTo($candidate, InterviewScheduled::class, fn ($n) => $n->event === 'rescheduled');
    }

    public function test_interviewers_endpoint_lists_staff(): void
    {
        $recruiter = $this->recruiterUser();
        $other = $this->recruiterUser();

        $this->actingAs($recruiter, 'sanctum')
            ->getJson('/api/v1/recruiter/interviewers')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}