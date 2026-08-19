<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\JobOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    public function store(Request $request, JobOffer $job): JsonResponse
    {
        $candidate = $request->user()->candidate;

        if (! $candidate) {
            throw ValidationException::withMessages(['profile' => 'Complete your candidate profile before applying.']);
        }

        // ponytail: business rules inline — two rules, no service layer yet.
        if ($job->status !== JobStatus::PUBLISHED) {
            throw ValidationException::withMessages(['job' => 'This job is not open for applications.']);
        }

        if (Application::where('job_offer_id', $job->id)->where('candidate_id', $candidate->id)->exists()) {
            throw ValidationException::withMessages(['job' => 'You have already applied to this job.']);
        }

        $data = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:5000'],
        ]);

        $application = Application::create([
            'job_offer_id' => $job->id,
            'candidate_id' => $candidate->id,
            'status' => ApplicationStatus::APPLIED,
            'cover_letter' => $data['cover_letter'] ?? null,
            'applied_at' => now(),
            'source' => 'web',
        ]);

        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'from_status' => null,
            'to_status' => ApplicationStatus::APPLIED,
            'changed_by' => $request->user()->id,
        ]);

        return (new ApplicationResource($application->load('jobOffer.company')))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $candidate = $request->user()->candidate;
        $applications = $candidate
            ? $candidate->applications()->with('jobOffer.company')->latest()->get()
            : collect();

        return ApplicationResource::collection($applications);
    }
}