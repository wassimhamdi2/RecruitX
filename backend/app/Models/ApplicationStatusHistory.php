<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $fillable = [
        'application_id',
        'from_status',
        'to_status',
        'changed_by',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ApplicationStatus::class,
            'to_status' => ApplicationStatus::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}