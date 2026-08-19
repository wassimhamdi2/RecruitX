<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'overall_score' => $this->overall_score,
            'recommendation' => $this->recommendation,
            'comments' => $this->comments,
            'created_at' => $this->created_at,
            'candidate' => $this->whenLoaded('application', fn () => [
                'id' => $this->application->candidate->id,
                'name' => $this->application->candidate->first_name.' '.$this->application->candidate->last_name,
            ]),
            'job' => $this->whenLoaded('application', fn () => [
                'title' => $this->application->jobOffer->title,
                'company' => $this->application->jobOffer->company->name ?? null,
            ]),
            'interview' => $this->whenLoaded('interview', fn () => [
                'id' => $this->interview->id,
                'type' => $this->interview->type,
                'scheduled_at' => $this->interview->scheduled_at,
                'status' => $this->interview->status,
            ]),
            'evaluator' => $this->whenLoaded('evaluator', fn () => $this->evaluator->name),
            'scores' => $this->whenLoaded('scores', fn () => $this->scores->map(
                fn ($s) => [
                    'criterion' => $s->criterion->name,
                    'max_score' => $s->criterion->max_score,
                    'score' => $s->score,
                    'comment' => $s->comment,
                ]
            )),
        ];
    }
}