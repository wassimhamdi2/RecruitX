<?php

namespace Tests\Feature\Api;

use App\Models\Candidate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvParseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function candidateUser(array $profile = []): User
    {
        $user = User::factory()->create();
        $user->assignRole('candidate');
        Candidate::create(array_merge([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Candidate',
        ], $profile));

        return $user;
    }

    private function parsedPayload(): array
    {
        return [
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'phone' => '+1 555 123 4567',
            'address' => '123 Main St',
            'skills' => ['Laravel', 'Docker'],
            'education' => [
                ['institution' => 'State University', 'degree' => 'BSc', 'field_of_study' => 'CS', 'start_date' => '2010', 'end_date' => '2014'],
            ],
            'experiences' => [
                ['company_name' => 'Acme Corp', 'position' => 'Developer', 'start_date' => '2019', 'end_date' => '2023', 'is_current' => false],
            ],
        ];
    }

    private function uploadCv(User $user): void
    {
        $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/me/cv', ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertOk();
    }

    public function test_parse_stores_parsed_data(): void
    {
        Http::fake(['*/parse' => Http::response($this->parsedPayload(), 200)]);
        $user = $this->candidateUser();
        $this->uploadCv($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/cv/parse')
            ->assertOk()
            ->assertJsonPath('data.parse_status', 'parsed')
            ->assertJsonPath('data.parsed.name', 'John Smith');

        $this->assertDatabaseHas('candidate_documents', [
            'candidate_id' => $user->candidate->id,
            'parse_status' => 'parsed',
        ]);
    }

    public function test_parse_requires_uploaded_cv(): void
    {
        $user = $this->candidateUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/cv/parse')
            ->assertStatus(422);
    }

    public function test_parse_surfaces_parser_error(): void
    {
        Http::fake(['*/parse' => Http::response(['detail' => 'no extractable text'], 422)]);
        $user = $this->candidateUser();
        $this->uploadCv($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/cv/parse')
            ->assertStatus(422)
            ->assertJsonPath('message', 'no extractable text');
    }

    public function test_apply_writes_profile_skills_education_experience(): void
    {
        Http::fake(['*/parse' => Http::response($this->parsedPayload(), 200)]);
        $user = $this->candidateUser();
        $this->uploadCv($user);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/me/cv/parse')->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/cv/apply')
            ->assertOk()
            ->assertJsonPath('data.parse_status', 'applied');

        $this->assertDatabaseHas('candidates', ['user_id' => $user->id, 'phone' => '+1 555 123 4567', 'address' => '123 Main St']);
        $this->assertDatabaseHas('candidate_skills', ['candidate_id' => $user->candidate->id]);
        $this->assertDatabaseHas('candidate_educations', ['candidate_id' => $user->candidate->id, 'institution' => 'State University', 'start_date' => '2010-01-01 00:00:00']);
        $this->assertDatabaseHas('candidate_experiences', ['candidate_id' => $user->candidate->id, 'company_name' => 'Acme Corp']);
        $this->assertDatabaseHas('candidate_documents', ['candidate_id' => $user->candidate->id, 'parse_status' => 'applied']);
    }

    public function test_apply_does_not_overwrite_existing_profile_fields(): void
    {
        Http::fake(['*/parse' => Http::response($this->parsedPayload(), 200)]);
        $user = $this->candidateUser(['phone' => '099 111 222']);
        $this->uploadCv($user);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/me/cv/parse')->assertOk();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/me/cv/apply')->assertOk();

        $this->assertDatabaseHas('candidates', ['user_id' => $user->id, 'phone' => '099 111 222']);
    }

    public function test_apply_requires_prior_parse(): void
    {
        $user = $this->candidateUser();
        $this->uploadCv($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/cv/apply')
            ->assertStatus(422);
    }

    public function test_parse_is_forbidden_for_recruiters(): void
    {
        $user = User::factory()->create();
        $user->assignRole('recruiter');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/cv/parse')
            ->assertForbidden();
    }
}