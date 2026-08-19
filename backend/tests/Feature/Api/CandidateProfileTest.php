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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
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

    private function uploadCv(User $user): void
    {
        $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/me/cv', ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertOk();
    }

    public function test_candidate_can_update_profile(): void
    {
        $user = $this->candidateUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/profile', [
                'city' => 'Paris',
                'bio' => 'Full-stack dev.',
                'years_of_experience' => 5,
                'linkedin_url' => 'https://linkedin.com/in/test',
            ])
            ->assertOk()
            ->assertJsonPath('data.city', 'Paris');

        $this->assertDatabaseHas('candidates', ['user_id' => $user->id, 'city' => 'Paris']);
    }

    public function test_candidate_can_upload_cv(): void
    {
        $user = $this->candidateUser();

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/v1/me/cv', ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]);

        $response->assertOk()->assertJsonPath('data.has_cv', true);

        $cv = $user->candidate->cv;
        $this->assertNotNull($cv);
        Storage::disk('local')->assertExists($cv->file_path);
    }

    public function test_cv_upload_rejects_wrong_mime(): void
    {
        $user = $this->candidateUser();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/me/cv', ['cv' => UploadedFile::fake()->create('cv.txt', 100, 'text/plain')])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cv');

        $this->assertDatabaseCount('candidate_documents', 0);
    }

    public function test_cv_upload_rejects_oversized_file(): void
    {
        $user = $this->candidateUser();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/me/cv', ['cv' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cv');
    }

    public function test_candidate_can_download_own_cv(): void
    {
        $user = $this->candidateUser();
        $this->uploadCv($user);

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/me/cv')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=cv.pdf');
    }

    public function test_uploading_new_cv_replaces_old_file(): void
    {
        $user = $this->candidateUser();
        $this->uploadCv($user);
        $oldPath = $user->candidate->cv->file_path;

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/me/cv', ['cv' => UploadedFile::fake()->create('new.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])
            ->assertOk();

        $this->assertCount(1, $user->candidate->documents()->where('type', 'cv')->get());
        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_recruiter_can_download_application_cv(): void
    {
        $candidate = $this->candidateUser();
        $this->uploadCv($candidate);

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
            'candidate_id' => $candidate->candidate->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->get("/api/v1/applications/{$application->id}/cv")
            ->assertOk();
    }

    public function test_candidate_cannot_access_application_cv(): void
    {
        $candidate = $this->candidateUser();
        $this->uploadCv($candidate);

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
            'candidate_id' => $candidate->candidate->id,
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $this->actingAs($candidate, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/cv")
            ->assertForbidden();
    }

    public function test_recruiter_cannot_access_candidate_profile_routes(): void
    {
        $this->actingAs($this->recruiterUser(), 'sanctum')
            ->getJson('/api/v1/me/profile')
            ->assertForbidden();
    }
}