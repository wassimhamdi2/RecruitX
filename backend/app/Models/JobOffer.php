<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOffer extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'slug',
        'description',
        'requirements',
        'responsibilities',
        'employment_type',
        'work_mode',
        'location',
        'salary_min',
        'salary_max',
        'currency',
        'experience_min',
        'experience_max',
        'status',
        'published_at',
        'closing_date',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'work_mode' => WorkMode::class,
            'status' => JobStatus::class,
            'published_at' => 'datetime',
            'closing_date' => 'date',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_offer_skills')
            ->withPivot('required_level', 'is_required');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}