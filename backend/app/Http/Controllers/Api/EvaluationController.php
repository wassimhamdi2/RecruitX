<?php

namespace App\Http\Controllers\Api;

use App\Enums\Recommendation;
use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationResource;
use App\Models\Application;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Interview;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function store(Request $request, Application $application, Interview $interview): JsonResponse
    {
        abort_unless($interview->application_id === $application->id, 422, 'Interview does not belong to this application.');

        $data = $request->validate([
            'recommendation' => ['required', Rule::enum(Recommendation::class)],
            'comments' => ['nullable', 'string', 'max:5000'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.criterion_id' => ['required', 'integer', 'exists:evaluation_criteria,id'],
            'scores.*.score' => ['required', 'integer', 'min:0'],
            'scores.*.comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // ponytail: manual max_score check — dynamic per-criterion limits don't fit array validation.
        foreach ($data['scores'] as $score) {
            $criterion = \App\Models\EvaluationCriterion::find($score['criterion_id']);
            if ($score['score'] > $criterion->max_score) {
                throw ValidationException::withMessages([
                    "scores.{$score['criterion_id']}.score" => "Score cannot exceed {$criterion->max_score} for {$criterion->name}.",
                ]);
            }
        }

        $evaluation = Evaluation::create([
            'application_id' => $application->id,
            'interview_id' => $interview->id,
            'evaluator_id' => $request->user()->id,
            'recommendation' => $data['recommendation'],
            'comments' => $data['comments'] ?? null,
        ]);

        $total = 0;
        $weightSum = 0;

        foreach ($data['scores'] as $score) {
            $criterion = \App\Models\EvaluationCriterion::find($score['criterion_id']);
            EvaluationScore::create([
                'evaluation_id' => $evaluation->id,
                'criterion_id' => $criterion->id,
                'score' => $score['score'],
                'comment' => $score['comment'] ?? null,
            ]);
            $total += $score['score'] * $criterion->weight;
            $weightSum += $criterion->weight;
        }

        $evaluation->update([
            'overall_score' => $weightSum > 0 ? round($total / $weightSum, 2) : round(array_sum(array_column($data['scores'], 'score')) / count($data['scores']), 2),
        ]);

        Audit::record('evaluation.created', $evaluation, null, [
            'interview_id' => $interview->id,
            'recommendation' => $evaluation->recommendation->value,
            'overall_score' => $evaluation->overall_score,
        ]);

        return (new EvaluationResource($evaluation->load('application.candidate', 'application.jobOffer.company', 'interview', 'scores.criterion')))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $evaluations = Evaluation::query()
            ->with('application.candidate', 'application.jobOffer.company', 'interview', 'scores.criterion', 'evaluator')
            ->when($request->application_id, fn ($q, $id) => $q->where('application_id', $id))
            ->when($request->interview_id, fn ($q, $id) => $q->where('interview_id', $id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return EvaluationResource::collection($evaluations);
    }

    public function show(Evaluation $evaluation): EvaluationResource
    {
        return new EvaluationResource(
            $evaluation->load('application.candidate', 'application.jobOffer.company', 'interview', 'scores.criterion', 'evaluator')
        );
    }

    public function criteria(): JsonResponse
    {
        return response()->json([
            'data' => \App\Models\EvaluationCriterion::orderBy('weight', 'desc')->get(),
        ]);
    }
}