<?php

namespace Tests\Feature\Api;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\InterviewScheduled;
use App\Notifications\NewApplicationReceived;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    private function job(User $recruiter): JobOffer
    {
        return JobOffer::create([
            'company_id' => Company::create(['name' => 'Test Co'])->id,
            'created_by' => $recruiter->id,
            'title' => 'Laravel Developer',
            'slug' => 'laravel-developer-'.uniqid(),
            'description' => 'Build APIs.',
            'employment_type' => 'full_time',
            'work_mode' => 'hybrid',
            'status' => JobStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function test_new_application_notifies_job_creator(): void
    {
        $recruiter = $this->recruiterUser();
        $job = $this->job($recruiter);
        $candidate = $this->candidateUser();

        $this->actingAs($candidate, 'sanctum')
            ->postJson("/api/v1/jobs/{$job->id}/apply")
            ->assertCreated();

        Notification::assertSentTo($recruiter, NewApplicationReceived::class);
    }

    public function test_status_change_notifies_candidate(): void
    {
        $candidate = $this->candidateUser();
        $recruiter = $this->recruiterUser();
        $job = $this->job($recruiter);
        $application = Application::create([
            'job_offer_id' => $job->id,
            'candidate_id' => $candidate->candidate->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $this->actingAs($recruiter, 'sanctum')
            ->patchJson("/api/v1/applications/{$application->id}/status", ['status' => 'screening'])
            ->assertOk();

        Notification::assertSentTo($candidate, ApplicationStatusChanged::class);
    }

    public function test_interview_schedule_notifies_candidate(): void
    {
        $candidate = $this->candidateUser();
        $recruiter = $this->recruiterUser();
        $job = $this->job($recruiter);
        $application = Application::create([
            'job_offer_id' => $job->id,
            'candidate_id' => $candidate->candidate->id,
            'status' => 'screening',
            'applied_at' => now(),
        ]);

        $this->actingAs($recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'type' => 'video',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertCreated();

        Notification::assertSentTo($candidate, InterviewScheduled::class);
    }

    private function createNotification(User $user, string $message): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => ApplicationStatusChanged::class,
            'data' => ['message' => $message],
        ]);

        return $id;
    }

    public function test_candidate_can_list_and_read_notifications(): void
    {
        $candidate = $this->candidateUser();
        $notificationId = $this->createNotification($candidate, 'Your application is now screening.');

        $response = $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/me/notifications')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));

        $this->actingAs($candidate, 'sanctum')
            ->patchJson("/api/v1/me/notifications/{$notificationId}/read")
            ->assertOk();

        $this->assertNotNull($candidate->notifications()->where('id', $notificationId)->first()->read_at);
    }

    public function test_unread_count_and_mark_all_read(): void
    {
        $candidate = $this->candidateUser();
        $this->createNotification($candidate, 'One');
        $this->createNotification($candidate, 'Two');

        $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/me/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->actingAs($candidate, 'sanctum')
            ->postJson('/api/v1/me/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $candidate->unreadNotifications()->count());
    }

    public function test_cannot_read_another_users_notification(): void
    {
        $candidate = $this->candidateUser();
        $other = $this->candidateUser();
        $notificationId = $this->createNotification($other, 'Private');

        $this->actingAs($candidate, 'sanctum')
            ->patchJson("/api/v1/me/notifications/{$notificationId}/read")
            ->assertNotFound();
    }
}