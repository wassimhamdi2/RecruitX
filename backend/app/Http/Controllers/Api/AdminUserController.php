<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->get('q'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
            ]),
            'meta' => ['total' => $users->total(), 'per_page' => $users->perPage()],
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate(['role' => ['required', 'in:admin,recruiter,candidate']]);

        $before = $user->roles->pluck('name')->all();
        $user->syncRoles([$data['role']]);
        Audit::record('user.role_changed', $user, ['roles' => $before], ['roles' => [$data['role']]]);

        return response()->json(['data' => ['id' => $user->id, 'roles' => $user->roles->pluck('name')]]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        Audit::record('user.deleted', $user, null, ['email' => $user->email]);
        $user->delete();

        return response()->json(['ok' => true]);
    }
}