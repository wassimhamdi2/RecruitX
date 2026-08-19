<?php

namespace App\Models;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interview extends Model
{
    protected $fillable = [
        'application_id',
        'scheduled_by',
        'type',
        'scheduled_at',
        'duration',
        'location',
        'meeting_url',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InterviewType::class,
            'status' => InterviewStatus::class,
            'scheduled_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(InterviewParticipant::class);
    }
}