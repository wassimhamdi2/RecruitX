<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($candidate = $request->user()->candidate, 403);

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
}