<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobOfferResource;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JobOfferController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $jobs = JobOffer::query()
            ->with('company', 'skills')
            ->where('status', JobStatus::PUBLISHED)
            ->when($request->keyword, fn ($q, $kw) => $q->where(function ($q) use ($kw) {
                $q->where('title', 'like', "%$kw%")
                    ->orWhere('location', 'like', "%$kw%");
            }))
            ->when($request->employment_type, fn ($q, $v) => $q->where('employment_type', $v))
            ->when($request->work_mode, fn ($q, $v) => $q->where('work_mode', $v))
            ->when($request->location, fn ($q, $v) => $q->where('location', 'like', "%$v%"))
            ->when($request->experience_min, fn ($q, $v) => $q->where('experience_max', '>=', $v))
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 15));

        return JobOfferResource::collection($jobs);
    }

    public function show(JobOffer $job): JobOfferResource
    {
        abort_unless($job->status === JobStatus::PUBLISHED, 404);

        return new JobOfferResource($job->load('company', 'skills'));
    }
}