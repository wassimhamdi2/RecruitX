<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'responsibilities' => $this->responsibilities,
            'company' => $this->company->name,
            'company_id' => $this->company_id,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'work_mode' => $this->work_mode,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency,
            'experience_min' => $this->experience_min,
            'experience_max' => $this->experience_max,
            'closing_date' => $this->closing_date,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'skills' => $this->whenLoaded('skills', $this->skills->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'required_level' => $s->pivot->required_level,
                'is_required' => (bool) $s->pivot->is_required,
            ])),
        ];
    }
}