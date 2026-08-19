<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Skill::orderBy('name')->get(['id', 'name'])]);
    }
}