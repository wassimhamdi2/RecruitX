<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkMode;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobOfferResource;
use App\Models\JobOffer;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function staffIndex(Request $request): AnonymousResourceCollection
    {
        $query = JobOffer::with('company', 'skills')
            ->when($request->user()->hasRole('recruiter'), fn ($q) => $q->where('created_by', $request->user()->id))
            ->orderByDesc('created_at');

        return JobOfferResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JobOfferResource
    {
        $data = $this->validated($request);

        $job = JobOffer::create([
            ...collect($data)->except('skills')->all(),
            'created_by' => $request->user()->id,
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'published_at' => $data['status'] === JobStatus::PUBLISHED->value ? now() : null,
        ]);

        if (! empty($data['skills'])) {
            $job->skills()->sync($data['skills']);
        }

        Audit::record('job.created', $job, null, ['title' => $job->title, 'company_id' => $job->company_id]);

        return new JobOfferResource($job->load('company', 'skills'));
    }

    public function update(Request $request, JobOffer $job): JobOfferResource
    {
        $this->authorizeJob($request, $job);

        $data = $this->validated($request, partial: true);

        $before = $job->only(['title', 'status', 'salary_min', 'salary_max']);
        $job->fill(collect($data)->except('skills')->all());
        if ($job->isDirty('title')) {
            $job->slug = Str::slug($data['title'] ?? $job->title).'-'.Str::lower(Str::random(6));
        }
        if ($job->status === JobStatus::PUBLISHED && $job->published_at === null) {
            $job->published_at = now();
        }
        $job->save();

        if (isset($data['skills'])) {
            $job->skills()->sync($data['skills']);
        }

        Audit::record('job.updated', $job, $before, $job->only(['title', 'status', 'salary_min', 'salary_max']));

        return new JobOfferResource($job->load('company', 'skills'));
    }

    public function destroy(Request $request, JobOffer $job): \Illuminate\Http\JsonResponse
    {
        $this->authorizeJob($request, $job);

        Audit::record('job.deleted', $job, null, ['title' => $job->title]);
        $job->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeJob(Request $request, JobOffer $job): void
    {
        abort_unless(
            $request->user()->hasRole('admin') || $job->created_by === $request->user()->id,
            403,
            'You can only manage your own job offers.'
        );
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'work_mode' => ['required', Rule::enum(WorkMode::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('salary_max') !== null && $value > (float) $request->input('salary_max')) {
                    $fail('The salary min must be less than or equal to salary max.');
                }
            }],
            'salary_max' => ['nullable', 'numeric', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('salary_min') !== null && $value < (float) $request->input('salary_min')) {
                    $fail('The salary max must be greater than or equal to salary min.');
                }
            }],
            'currency' => ['nullable', 'string', 'size:3'],
            'experience_min' => ['nullable', 'integer', 'min:0', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('experience_max') !== null && $value > (int) $request->input('experience_max')) {
                    $fail('The experience min must be less than or equal to experience max.');
                }
            }],
            'experience_max' => ['nullable', 'integer', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('experience_min') !== null && $value < (int) $request->input('experience_min')) {
                    $fail('The experience max must be greater than or equal to experience min.');
                }
            }],
            'status' => [Rule::enum(JobStatus::class)],
            'closing_date' => ['nullable', 'date', 'after:today'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ];

        if ($partial) {
            $rules = array_map(
                fn (array $r) => array_map(fn ($rule) => $rule === 'required' ? 'sometimes' : $rule, $r),
                $rules,
            );
        }

        return $request->validate($rules);
    }
}