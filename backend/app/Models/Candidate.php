<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'address',
        'city',
        'country',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'bio',
        'years_of_experience',
        'availability',
        'expected_salary',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'expected_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}