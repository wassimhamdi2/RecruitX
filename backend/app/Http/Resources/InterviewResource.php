<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'type' => $this->type,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'duration' => $this->duration,
            'location' => $this->location,
            'meeting_url' => $this->meeting_url,
            'notes' => $this->notes,
            'candidate' => $this->whenLoaded('application', fn () => [
                'id' => $this->application->candidate->id,
                'name' => $this->application->candidate->first_name.' '.$this->application->candidate->last_name,
            ]),
            'job' => $this->whenLoaded('application.jobOffer', fn () => [
                'title' => $this->application->jobOffer->title,
                'company' => $this->application->jobOffer->company->name ?? null,
            ]),
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(
                fn ($p) => ['name' => $p->user?->name, 'role' => $p->role]
            )),
        ];
    }
}