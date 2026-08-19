import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { applyToJob, getJob } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button, Card } from '../components/ui'

export default function JobDetail() {
  const { slug } = useParams()
  const [applied, setApplied] = useState(false)
  const [error, setError] = useState('')
  const { data, isLoading } = useQuery({
    queryKey: ['job', slug],
    queryFn: () => getJob(slug!).then((r) => r.data.data),
  })

  const handleApply = async () => {
    try {
      await applyToJob(data!.id)
      setApplied(true)
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
          {data.company} · {data.location} · {data.work_mode} · {data.employment_type.replace('_', ' ')}
        </p>
        {data.salary_min && data.salary_max && (
          <p className="mt-1 text-sm font-medium text-accent">
            {data.salary_min} – {data.salary_max} {data.currency}
          </p>
        )}

        <div className="mt-6 flex flex-wrap gap-2">
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
          <div className="whitespace-pre-line text-sm leading-relaxed">{data.description}</div>
        </Card>

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