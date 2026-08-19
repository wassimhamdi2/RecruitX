import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { applyToJob, getJob, myApplications } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button, Card } from '../components/ui'

export default function JobDetail() {
  const { slug } = useParams()
  const [error, setError] = useState('')
  const { data, isLoading } = useQuery({
    queryKey: ['job', slug],
    queryFn: () => getJob(slug!).then((r) => r.data.data),
  })
  const mine = useQuery({
    queryKey: ['my-applications'],
    queryFn: () => myApplications().then((r) => r.data.data),
    enabled: !!data,
  })

  const alreadyApplied = mine.data?.some((a) => a.job.id === data?.id) ?? false
  const applied = alreadyApplied

  const handleApply = async () => {
    try {
      await applyToJob(data!.id)
      setError('Applied successfully')
    } catch (err: unknown) {
      const message = (err as { response?: { data?: { errors?: Record<string, string[]> } } }).response?.data
        ?.errors
      setError(message ? Object.values(message)[0][0] : 'Apply failed')
    }
  }

  if (isLoading) return <AppLayout><p className="py-10 text-foreground/60">Loading…</p></AppLayout>

  if (!data) return <AppLayout><p className="py-10 text-foreground/60">Job not found.</p></AppLayout>

  return (
    <AppLayout>
      <div className="max-w-3xl py-10">
        <h1 className="font-display text-3xl font-semibold">{data.title}</h1>
        <p className="mt-2 text-sm text-foreground/70">
          {data.company} · {data.location || 'Remote/Any'} · {data.work_mode} · {data.employment_type.replace('_', ' ')}
        </p>
        {(data.salary_min || data.salary_max) && (
          <p className="mt-1 text-sm font-medium text-accent">
            {data.salary_min ? data.salary_min : 0} – {data.salary_max ? data.salary_max : '∞'} {data.currency}
          </p>
        )}

        <div className="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-foreground/70">
          {data.experience_min != null || data.experience_max != null ? (
            <span>Experience: {data.experience_min ?? 0}–{data.experience_max ?? '∞'} yrs</span>
          ) : null}
          {data.closing_date ? <span>Closes: {new Date(data.closing_date).toLocaleDateString()}</span> : null}
        </div>

        <div className="mt-4 flex flex-wrap gap-2">
          {data.skills?.map((s) => (
            <span
              key={s.name}
              className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
            >
              {s.name} {s.is_required ? '(required)' : ''}
            </span>
          ))}
        </div>

        <Card className="mt-6">
          <h2 className="font-display text-lg font-semibold">About the role</h2>
          <div className="mt-2 whitespace-pre-line text-sm leading-relaxed">{data.description}</div>
        </Card>

        {data.requirements ? (
          <Card className="mt-4">
            <h2 className="font-display text-lg font-semibold">Requirements</h2>
            <div className="mt-2 whitespace-pre-line text-sm leading-relaxed">{data.requirements}</div>
          </Card>
        ) : null}

        {data.responsibilities ? (
          <Card className="mt-4">
            <h2 className="font-display text-lg font-semibold">Responsibilities</h2>
            <div className="mt-2 whitespace-pre-line text-sm leading-relaxed">{data.responsibilities}</div>
          </Card>
        ) : null}

        <div className="mt-8">
          {applied ? (
            <Button disabled>Applied successfully</Button>
          ) : (
            <Button onClick={handleApply}>Apply now</Button>
          )}
          {error && <p className="mt-2 text-sm text-destructive">{error}</p>}
        </div>
      </div>
    </AppLayout>
  )
}