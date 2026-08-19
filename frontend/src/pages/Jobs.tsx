import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { listJobs } from '../services/api'

export default function Jobs() {
  const [keyword, setKeyword] = useState('')
  const [filters, setFilters] = useState<Record<string, string>>({})
  const { data, isLoading } = useQuery({
    queryKey: ['jobs', filters],
    queryFn: () => listJobs(filters).then((r) => r.data.data),
  })

  return (
    <div className="mx-auto max-w-4xl px-8 py-10">
      <form
        className="mb-6 flex gap-2"
        onSubmit={(e) => {
          e.preventDefault()
          setFilters({ keyword })
        }}
      >
        <input
          value={keyword}
          onChange={(e) => setKeyword(e.target.value)}
          placeholder="Search by title or location"
          className="w-full rounded border px-3 py-2"
        />
        <button className="rounded bg-blue-600 px-4 py-2 text-white">Search</button>
      </form>

      {isLoading && <p className="text-slate-500">Loading...</p>}

      <div className="space-y-3">
        {data?.map((job) => (
          <Link
            key={job.id}
            to={`/jobs/${job.slug}`}
            className="block rounded-lg border bg-white p-5 shadow-sm hover:border-blue-300"
          >
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold">{job.title}</h2>
              <span className="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">
                {job.work_mode}
              </span>
            </div>
            <p className="mt-1 text-sm text-slate-600">
              {job.company} · {job.location} · {job.employment_type}
            </p>
          </Link>
        ))}
        {data?.length === 0 && <p className="text-slate-500">No jobs found.</p>}
      </div>
    </div>
  )
}