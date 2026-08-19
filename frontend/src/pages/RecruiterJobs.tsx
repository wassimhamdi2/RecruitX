import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { companies, createJob, deleteJob, myJobs, skills, updateJob } from '../services/api'
import type { Job } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button } from '../components/ui'

const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'contract', 'internship', 'freelance']
const WORK_MODES = ['on_site', 'remote', 'hybrid']
const JOB_STATUSES = ['draft', 'published', 'closed', 'archived']

const EMPTY_FORM = {
  company_id: '',
  title: '',
  description: '',
  requirements: '',
  responsibilities: '',
  employment_type: 'full_time',
  work_mode: 'hybrid',
  location: '',
  salary_min: '',
  salary_max: '',
  currency: 'USD',
  experience_min: '',
  experience_max: '',
  status: 'draft',
  closing_date: '',
}

export default function RecruiterJobs() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<Job | null>(null)

  const jobs = useQuery({
    queryKey: ['my-jobs'],
    queryFn: () => myJobs().then((r) => r.data.data),
  })

  const remove = useMutation({
    mutationFn: (id: number) => deleteJob(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['my-jobs'] }),
  })

  return (
    <AppLayout>
      <div className="py-10">
        <div className="flex items-center justify-between">
          <h1 className="font-display text-2xl font-semibold">My Job Offers</h1>
          <Button onClick={() => setEditing({} as Job)}>New Job</Button>
        </div>

        <div className="mt-6 grid gap-4 sm:grid-cols-2">
          {jobs.data?.map((job) => (
            <div key={job.id} className="rounded-xl border border-white/60 bg-white/70 p-5 shadow-sm backdrop-blur-md">
              <div className="flex items-start justify-between">
                <div>
                  <Link to={`/jobs/${job.slug}`} className="font-display text-lg font-semibold text-primary hover:underline">
                    {job.title}
                  </Link>
                  <p className="text-sm text-foreground/60">
                    {job.company} · {job.location ?? 'Remote/Any'}
                  </p>
                </div>
                <span
                  className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                    job.status === 'published' ? 'bg-accent/10 text-accent' : job.status === 'closed' ? 'bg-red-500/10 text-red-600' : 'bg-slate-200/70 text-slate-600'
                  }`}
                >
                  {job.status}
                </span>
              </div>
              <div className="mt-4 flex gap-2">
                <Button variant="ghost" onClick={() => setEditing(job)}>
                  Edit
                </Button>
                <Button
                  variant="ghost"
                  onClick={() => {
                    if (confirm(`Delete job "${job.title}"?`)) remove.mutate(job.id)
                  }}
                >
                  Delete
                </Button>
              </div>
            </div>
          ))}
          {!jobs.data?.length ? <p className="text-sm text-foreground/60">No jobs yet. Create your first one.</p> : null}
        </div>
      </div>

      {editing ? <JobForm job={editing} onClose={() => setEditing(null)} /> : null}
    </AppLayout>
  )
}

function JobForm({ job, onClose }: { job: Job; onClose: () => void }) {
  const qc = useQueryClient()
  const isEdit = Boolean(job.id)

  const companiesQuery = useQuery({
    queryKey: ['companies'],
    queryFn: () => companies().then((r) => r.data.data),
  })

  const [form, setForm] = useState(() =>
    isEdit
      ? {
          company_id: String(job.company_id),
          title: job.title,
          description: job.description,
          requirements: job.requirements ?? '',
          responsibilities: job.responsibilities ?? '',
          employment_type: job.employment_type,
          work_mode: job.work_mode,
          location: job.location ?? '',
          salary_min: job.salary_min ?? '',
          salary_max: job.salary_max ?? '',
          currency: job.currency ?? 'USD',
          experience_min: job.experience_min ?? '',
          experience_max: job.experience_max ?? '',
          status: job.status ?? 'draft',
          closing_date: job.closing_date ?? '',
        }
      : EMPTY_FORM,
  )
  const [skillIds, setSkillIds] = useState<number[]>(() => (isEdit ? job.skills.map((s) => s.id) : []))
  const skillsQuery = useQuery({
    queryKey: ['skills'],
    queryFn: () => skills().then((r) => r.data.data),
  })
  const [error, setError] = useState('')

  const save = useMutation({
    mutationFn: () => {
      const payload: Record<string, unknown> = { ...form, company_id: Number(form.company_id) }
      for (const k of ['salary_min', 'salary_max', 'experience_min', 'experience_max']) {
        ;(payload as Record<string, unknown>)[k] = (form as Record<string, string>)[k] === '' ? null : Number((form as Record<string, string>)[k])
      }
      if (!form.closing_date) payload.closing_date = null
      payload.skills = skillIds
      return isEdit ? updateJob(job.id, payload) : createJob(payload)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['my-jobs'] })
      onClose()
    },
    onError: (e) => {
      const err = e as { response?: { data?: { message?: string } } }
      setError(err.response?.data?.message ?? 'Failed to save job.')
    },
  })

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
    setForm((f) => ({ ...f, [k]: e.target.value }))

  const input = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:outline-none'

  return (
    <div className="fixed inset-0 z-20 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-6 backdrop-blur-sm" onClick={onClose}>
      <div className="w-full max-w-2xl rounded-2xl border border-white/60 bg-white p-6 shadow-xl" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between">
          <h2 className="font-display text-xl font-semibold">{isEdit ? 'Edit Job' : 'New Job'}</h2>
          <button type="button" onClick={onClose} className="text-foreground/50 hover:text-foreground">
            ×
          </button>
        </div>

        {error ? <p className="mt-3 rounded-lg bg-red-500/10 px-3 py-2 text-sm text-red-600">{error}</p> : null}

        <div className="mt-4 grid gap-3 sm:grid-cols-2">
          <label className="text-sm">
            Company *
            <select value={form.company_id} onChange={set('company_id')} className={input}>
              <option value="">Select company…</option>
              {companiesQuery.data?.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </label>
          <label className="text-sm">
            Title *
            <input value={form.title} onChange={set('title')} className={input} />
          </label>
          <label className="text-sm sm:col-span-2">
            Description *
            <textarea value={form.description} onChange={set('description')} rows={3} className={input} />
          </label>
          <label className="text-sm">
            Employment type *
            <select value={form.employment_type} onChange={set('employment_type')} className={input}>
              {EMPLOYMENT_TYPES.map((t) => (
                <option key={t} value={t}>
                  {t.replace('_', ' ')}
                </option>
              ))}
            </select>
          </label>
          <label className="text-sm">
            Work mode *
            <select value={form.work_mode} onChange={set('work_mode')} className={input}>
              {WORK_MODES.map((m) => (
                <option key={m} value={m}>
                  {m.replace('_', ' ')}
                </option>
              ))}
            </select>
          </label>
          <label className="text-sm">
            Location
            <input value={form.location} onChange={set('location')} className={input} />
          </label>
          <label className="text-sm">
            Status
            <select value={form.status} onChange={set('status')} className={input}>
              {JOB_STATUSES.map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </select>
          </label>
          <label className="text-sm">
            Salary min
            <input value={form.salary_min} onChange={set('salary_min')} type="number" className={input} />
          </label>
          <label className="text-sm">
            Salary max
            <input value={form.salary_max} onChange={set('salary_max')} type="number" className={input} />
          </label>
          <label className="text-sm">
            Currency
            <input value={form.currency} onChange={set('currency')} maxLength={3} className={input} />
          </label>
          <label className="text-sm">
            Experience min (yrs)
            <input value={form.experience_min} onChange={set('experience_min')} type="number" className={input} />
          </label>
          <label className="text-sm">
            Experience max (yrs)
            <input value={form.experience_max} onChange={set('experience_max')} type="number" className={input} />
          </label>
          <label className="text-sm">
            Closing date
            <input value={form.closing_date} onChange={set('closing_date')} type="date" className={input} />
          </label>
        </div>

        <div className="mt-4">
          <span className="text-sm">Skills</span>
          <div className="mt-2 flex flex-wrap gap-2">
            {skillsQuery.data?.map((s) => (
              <label
                key={s.id}
                className={`cursor-pointer rounded-full border px-3 py-1 text-sm transition-colors ${
                  skillIds.includes(s.id)
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'border-line bg-white/70 text-foreground/70 hover:bg-white'
                }`}
              >
                <input
                  type="checkbox"
                  className="sr-only"
                  checked={skillIds.includes(s.id)}
                  onChange={() =>
                    setSkillIds((cur) => (cur.includes(s.id) ? cur.filter((i) => i !== s.id) : [...cur, s.id]))
                  }
                />
                {s.name}
              </label>
            ))}
          </div>
        </div>

        <div className="mt-5 flex justify-end gap-2">
          <Button variant="ghost" onClick={onClose}>
            Cancel
          </Button>
          <Button onClick={() => save.mutate()} disabled={save.isPending}>
            {isEdit ? 'Save changes' : 'Create job'}
          </Button>
        </div>
      </div>
    </div>
  )
}