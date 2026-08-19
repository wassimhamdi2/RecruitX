<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::with('user:id,name,email')
            ->when($request->get('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->get('action'), fn ($q, $action) => $q->where('action', $action))
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'user' => $log->user,
                'action' => $log->action,
                'auditable_type' => class_basename($log->auditable_type ?? ''),
                'auditable_id' => $log->auditable_id,
                'before' => $log->before,
                'after' => $log->after,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
            ]),
            'meta' => ['total' => $logs->total(), 'per_page' => $logs->perPage()],
        ]);
    }
}