<?php

namespace App\Models;

use App\Enums\Recommendation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $fillable = [
        'application_id',
        'interview_id',
        'evaluator_id',
        'overall_score',
        'recommendation',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'decimal:2',
            'recommendation' => Recommendation::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }
}