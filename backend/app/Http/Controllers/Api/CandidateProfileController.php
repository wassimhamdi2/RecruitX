<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\Skill;
use App\Services\CvParserService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($candidate = $request->user()->candidate, 403);

        $candidate->has_cv = (bool) $candidate->cv;
        $candidate->load('skills:id,name', 'educations', 'experiences');

        return response()->json(['data' => $candidate]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($candidate = $request->user()->candidate, 403);
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'availability' => ['nullable', 'string', 'max:50'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $request->user()->candidate->update($data);

        return response()->json(['data' => $request->user()->candidate->fresh()]);
    }

    public function uploadCv(Request $request): JsonResponse
    {
        abort_unless($candidate = $request->user()->candidate, 403);

        $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $file = $request->file('cv');

        $candidate->unsetRelation('cv');
        if ($candidate->cv) {
            Storage::disk('local')->delete($candidate->cv->file_path);
        }

        $path = $file->store('cvs', 'local');

        CandidateDocument::updateOrCreate(
            ['candidate_id' => $candidate->id, 'type' => 'cv'],
            [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'is_primary' => true,
            ],
        );

        $candidate->unsetRelation('cv');

        return response()->json([
            'data' => [
                'has_cv' => true,
                'file_name' => $file->getClientOriginalName(),
            ],
        ]);
    }

    public function downloadOwnCv(Request $request): StreamedResponse
    {
        $cv = $request->user()->candidate?->cv;

        abort_unless($cv, 404);

        return Storage::disk('local')->download($cv->file_path, $cv->file_name);
    }

    public function parseCv(Request $request, CvParserService $parser): JsonResponse
    {
        abort_unless($candidate = $request->user()->candidate, 403);

        $cv = $candidate->cv;
        abort_unless($cv, 422, 'Upload a CV first.');

        try {
            $parsed = $parser->parse($cv);
        } catch (RequestException $e) {
            $message = $e->response->json('detail') ?? 'Could not parse this CV.';
            return response()->json(['message' => $message], 422);
        } catch (ConnectionException) {
            return response()->json(['message' => 'CV parser service is unavailable.'], 503);
        }

        $cv->update([
            'parse_status' => 'parsed',
            'parsed_data' => $parsed,
        ]);

        return response()->json(['data' => ['parse_status' => 'parsed', 'parsed' => $parsed]]);
    }

    public function applyCv(Request $request): JsonResponse
    {
        abort_unless($candidate = $request->user()->candidate, 403);

        $cv = $candidate->cv;
        abort_unless($cv, 422, 'Upload a CV first.');
        abort_unless($parsed = $cv->parsed_data, 422, 'Parse your CV first.');

        $onlyNull = fn ($field, $value) => is_null($candidate->{$field}) && is_string($value) && trim($value) !== '';
        $updates = [];
        foreach (['phone', 'address', 'city', 'country'] as $field) {
            if ($onlyNull($field, $parsed[$field] ?? null)) {
                $updates[$field] = trim($parsed[$field]);
            }
        }
        if ($updates) {
            $candidate->update($updates);
        }

        $skillIds = [];
        foreach ($parsed['skills'] ?? [] as $skillName) {
            $skill = Skill::firstOrCreate(['name' => trim($skillName)]);
            $skillIds[] = $skill->id;
        }
        if ($skillIds) {
            $candidate->skills()->syncWithoutDetaching($skillIds);
        }

        $educations = 0;
        foreach ($parsed['education'] ?? [] as $ed) {
            $institution = trim($ed['institution'] ?? '');
            if ($institution === '') {
                continue;
            }
            $candidate->educations()->firstOrCreate([
                'institution' => $institution,
                'start_date' => $this->toDate($ed['start_date'] ?? null),
            ], [
                'degree' => $ed['degree'] ?? null,
                'field_of_study' => $ed['field_of_study'] ?? null,
                'end_date' => $this->toDate($ed['end_date'] ?? null),
            ]);
            $educations++;
        }

        $experiences = 0;
        foreach ($parsed['experiences'] ?? [] as $exp) {
            $company = trim($exp['company_name'] ?? '');
            $position = trim($exp['position'] ?? '');
            if ($company === '' || $position === '') {
                continue;
            }
            $candidate->experiences()->firstOrCreate([
                'company_name' => $company,
                'start_date' => $this->toDate($exp['start_date'] ?? null),
            ], [
                'position' => $position,
                'end_date' => $this->toDate($exp['end_date'] ?? null),
                'is_current' => (bool) ($exp['is_current'] ?? false),
            ]);
            $experiences++;
        }

        $cv->update(['parse_status' => 'applied']);

        return response()->json([
            'data' => [
                'parse_status' => 'applied',
                'skills_added' => count($skillIds),
                'educations_added' => $educations,
                'experiences_added' => $experiences,
            ],
        ]);
    }

    private function toDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        return preg_match('/^\d{4}$/', $value) ? $value.'-01-01' : $value;
    }
}