<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Audit
{
    public static function record(string $action, ?Model $auditable = null, ?array $before = null, ?array $after = null, ?int $userId = null): void
    {
        AuditLog::create([
            'user_id' => $userId ?? auth('sanctum')->id(),
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
        ]);
    }
}