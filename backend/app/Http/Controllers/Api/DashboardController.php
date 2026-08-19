<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Evaluation;
use App\Models\Interview;
use App\Models\JobOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // ponytail: recruiters see their own jobs' pipeline; admin sees everything.
        $isAdmin = $user->hasRole('admin');
        $scopeApps = fn ($query) => $isAdmin ? $query : $query->whereHas('jobOffer', fn ($q) => $q->where('created_by', $user->id));
        $scopeJobs = fn ($query) => $isAdmin ? $query : $query->where('created_by', $user->id);

        $applicationIds = $scopeApps(Application::query())->pluck('id');

        $statuses = $scopeApps(Application::query())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $days = collect(range(13, 0))->map(fn ($i) => [
            'date' => Carbon::today()->subDays($i)->toDateString(),
            'count' => 0,
        ])->keyBy('date');

        $scopeApps(Application::query())
            ->selectRaw('date(applied_at) as date, count(*) as total')
            ->where('applied_at', '>=', Carbon::today()->subDays(13)->startOfDay())
            ->groupBy('date')
            ->get()
            ->each(fn ($row) => $days->put($row->date, ['date' => $row->date, 'count' => $row->total]));

        $topJobs = $scopeJobs(JobOffer::query())
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'status'])
            ->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'applications_count' => $job->applications_count,
            ]);

        $interviewIds = Interview::whereIn('application_id', $applicationIds)->pluck('id');

        $avgScore = Evaluation::whereIn('interview_id', $interviewIds)->avg('overall_score');

        return response()->json([
            'data' => [
                'totals' => [
                    'jobs' => $scopeJobs(JobOffer::query())->count(),
                    'published_jobs' => $scopeJobs(JobOffer::query())->where('status', 'published')->count(),
                    'applications' => $applicationIds->count(),
                    'interviews' => $interviewIds->count(),
                    'completed_interviews' => Interview::whereIn('id', $interviewIds)->where('status', 'completed')->count(),
                    'evaluations' => Evaluation::whereIn('interview_id', $interviewIds)->count(),
                    'avg_evaluation_score' => $avgScore === null ? null : round((float) $avgScore, 2),
                ],
                'applications_by_status' => $statuses,
                'applications_last_14_days' => $days->values(),
                'top_jobs' => $topJobs,
            ],
        ]);
    }
}