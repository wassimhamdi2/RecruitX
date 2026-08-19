import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { listJobs } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button, Card, Input } from '../components/ui'

export default function Jobs() {
  const [keyword, setKeyword] = useState('')
  const [filters, setFilters] = useState<Record<string, string>>({})
  const { data, isLoading } = useQuery({
    queryKey: ['jobs', filters],
    queryFn: () => listJobs(filters).then((r) => r.data.data),
  })

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">Browse Jobs</h1>
        <p className="mt-1 text-sm text-foreground/70">Find your next role</p>

        <form
          className="mt-6 flex max-w-xl gap-2"
          onSubmit={(e) => {
            e.preventDefault()
            setFilters({ keyword })
          }}
        >
          <Input
            value={keyword}
            onChange={(e) => setKeyword(e.target.value)}
            placeholder="Search by title or location"
          />
          <Button type="submit">Search</Button>
        </form>

        <div className="mt-8 space-y-3">
          {isLoading && <p className="text-foreground/60">Loading…</p>}
          {data?.map((job) => (
            <Link
              key={job.id}
              to={`/jobs/${job.slug}`}
              className="block rounded-xl border border-white/60 bg-white/70 p-5 shadow-sm backdrop-blur-md transition-colors hover:border-secondary hover:bg-white"
            >
              <div className="flex items-center justify-between gap-4">
                <h2 className="font-display text-lg font-semibold">{job.title}</h2>
                <span className="shrink-0 rounded-full bg-secondary/10 px-3 py-1 text-xs font-medium capitalize text-primary">
                  {job.work_mode}
                </span>
              </div>
              <p className="mt-1 text-sm text-foreground/70">
                {job.company} · {job.location} · {job.employment_type.replace('_', ' ')}
              </p>
            </Link>
          ))}
          {data?.length === 0 && (
            <Card>
              <p className="text-foreground/60">No jobs found.</p>
            </Card>
          )}
        </div>
      </div>
    </AppLayout>
  )
}