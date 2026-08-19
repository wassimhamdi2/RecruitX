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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    // ponytail: transition map inline — single source of truth for the pipeline.
    private const TRANSITIONS = [
        ApplicationStatus::APPLIED->value => ['screening', 'rejected', 'withdrawn'],
        ApplicationStatus::SCREENING->value => ['shortlisted', 'rejected', 'withdrawn'],
        ApplicationStatus::SHORTLISTED->value => ['interview', 'rejected', 'withdrawn'],
        ApplicationStatus::INTERVIEW->value => ['evaluation', 'rejected', 'withdrawn'],
        ApplicationStatus::EVALUATION->value => ['offer', 'rejected', 'withdrawn'],
        ApplicationStatus::OFFER->value => ['hired', 'rejected', 'withdrawn'],
        ApplicationStatus::HIRED->value => [],
        ApplicationStatus::REJECTED->value => [],
        ApplicationStatus::WITHDRAWN->value => [],
    ];
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

    public function recruiterIndex(Request $request): AnonymousResourceCollection
    {
        $applications = Application::query()
            ->with('candidate.cv', 'jobOffer.company')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->job_id, fn ($q, $id) => $q->where('job_offer_id', $id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApplicationResource::collection($applications);
    }

    public function updateStatus(Request $request, Application $application): ApplicationResource
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::enum(ApplicationStatus::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $from = $application->status->value;
        $to = $data['status'];

        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move application from {$from} to {$to}.",
            ]);
        }

        $application->update(['status' => $to]);

        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $request->user()->id,
            'comment' => $data['comment'] ?? null,
        ]);

        return new ApplicationResource($application->load('candidate.cv', 'jobOffer.company'));
    }

    public function applicationCv(Application $application): StreamedResponse
    {
        $cv = $application->candidate?->cv;

        abort_unless($cv, 404);

        return Storage::disk('local')->download($cv->file_path, $cv->file_name);
    }
}