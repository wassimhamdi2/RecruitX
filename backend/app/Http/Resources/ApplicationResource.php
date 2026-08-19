<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'applied_at' => $this->applied_at,
            'candidate' => $this->whenLoaded('candidate', fn () => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->first_name.' '.$this->candidate->last_name,
            ]),
            'job' => $this->whenLoaded('jobOffer', fn () => [
                'id' => $this->jobOffer->id,
                'title' => $this->jobOffer->title,
                'slug' => $this->jobOffer->slug,
                'company' => $this->jobOffer->company->name ?? null,
                'location' => $this->jobOffer->location,
                'employment_type' => $this->jobOffer->employment_type,
                'work_mode' => $this->jobOffer->work_mode,
            ]),
        ];
    }
}