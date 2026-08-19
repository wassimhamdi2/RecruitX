import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { applyToJob, getJob } from '../services/api'

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

  if (isLoading) return <p className="px-8 py-10 text-slate-500">Loading...</p>
  if (!data) return <p className="px-8 py-10 text-slate-500">Job not found.</p>

  return (
    <div className="mx-auto max-w-3xl px-8 py-10">
      <h1 className="text-2xl font-semibold">{data.title}</h1>
      <p className="mt-1 text-slate-600">
        {data.company} · {data.location} · {data.work_mode} · {data.employment_type}
      </p>
      <p className="mt-1 text-slate-600">
        {data.salary_min && data.salary_max
          ? `${data.salary_min} - ${data.salary_max} ${data.currency}`
          : ''}
      </p>

      <div className="mt-6 whitespace-pre-line rounded-lg border bg-white p-5 shadow-sm">
        <p>{data.description}</p>
      </div>

      <div className="mt-4 flex flex-wrap gap-2">
        {data.skills?.map((s) => (
          <span key={s.name} className="rounded-full bg-blue-50 px-3 py-1 text-xs text-blue-700">
            {s.name} {s.is_required ? '(required)' : ''}
          </span>
        ))}
      </div>

      <div className="mt-8">
        {applied ? (
          <span className="rounded bg-green-100 px-4 py-2 text-sm text-green-700">
            Applied successfully
          </span>
        ) : (
          <button
            onClick={handleApply}
            className="rounded bg-blue-600 px-6 py-2 text-white hover:bg-blue-700"
          >
            Apply now
          </button>
        )}
        {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
      </div>
    </div>
  )
}