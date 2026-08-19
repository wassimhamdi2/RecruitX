<?php

namespace App\Http\Controllers\Api;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Http\Controllers\Controller;
use App\Http\Resources\InterviewResource;
use App\Models\Application;
use App\Models\Interview;
use App\Models\InterviewParticipant;
use App\Notifications\InterviewScheduled;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InterviewController extends Controller
{
    // ponytail: transition map inline — mirrors ApplicationController::TRANSITIONS.
    private const TRANSITIONS = [
        InterviewStatus::SCHEDULED->value => ['completed', 'cancelled', 'rescheduled', 'no_show'],
        InterviewStatus::RESCHEDULED->value => ['completed', 'cancelled', 'no_show'],
        InterviewStatus::COMPLETED->value => [],
        InterviewStatus::CANCELLED->value => [],
        InterviewStatus::NO_SHOW->value => [],
    ];

    public function store(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(InterviewType::class)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration' => ['nullable', 'integer', 'between:30,480'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'interviewers' => ['nullable', 'array'],
            'interviewers.*' => ['integer', 'exists:users,id'],
        ]);

        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_by' => $request->user()->id,
            'type' => $data['type'],
            'scheduled_at' => $data['scheduled_at'],
            'duration' => $data['duration'] ?? 60,
            'location' => $data['location'] ?? null,
            'meeting_url' => $data['meeting_url'] ?? null,
            'status' => InterviewStatus::SCHEDULED,
            'notes' => $data['notes'] ?? null,
        ]);

        InterviewParticipant::create([
            'interview_id' => $interview->id,
            'user_id' => $application->candidate->user_id,
            'role' => 'candidate',
        ]);

        foreach ($data['interviewers'] ?? [] as $userId) {
            InterviewParticipant::firstOrCreate([
                'interview_id' => $interview->id,
                'user_id' => $userId,
            ], ['role' => 'interviewer']);
        }

        $application->candidate->user->notify(new InterviewScheduled($interview->load('application.jobOffer')));

        return (new InterviewResource($interview->load('application.candidate', 'application.jobOffer.company', 'participants.user')))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $interviews = Interview::query()
            ->with('application.candidate', 'application.jobOffer.company', 'participants.user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->upcoming, fn ($q) => $q->where('scheduled_at', '>=', now()))
            ->latest('scheduled_at')
            ->paginate($request->integer('per_page', 15));

        return InterviewResource::collection($interviews);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        $candidate = $request->user()->candidate;

        $applicationIds = $candidate
            ? $candidate->applications()->pluck('id')
            : collect();

        $interviews = Interview::query()
            ->whereIn('application_id', $applicationIds)
            ->with('application.candidate', 'application.jobOffer.company', 'participants.user')
            ->latest('scheduled_at')
            ->get();

        return InterviewResource::collection($interviews);
    }

    public function update(Request $request, Interview $interview): InterviewResource
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::enum(InterviewStatus::class)],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (isset($data['status'])) {
            $from = $interview->status->value;
            $to = $data['status'];

            if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot move interview from {$from} to {$to}.",
                ]);
            }

            $interview->status = $to;
        }

        if (isset($data['scheduled_at'])) {
            $interview->scheduled_at = $data['scheduled_at'];
        }

        if (array_key_exists('notes', $data)) {
            $interview->notes = $data['notes'];
        }

        $interview->save();

        return new InterviewResource($interview->load('application.candidate', 'application.jobOffer.company', 'participants.user'));
    }
}