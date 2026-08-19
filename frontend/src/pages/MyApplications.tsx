import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { myApplications } from '../services/api'

export default function MyApplications() {
  const { data, isLoading } = useQuery({
    queryKey: ['applications'],
    queryFn: () => myApplications().then((r) => r.data.data),
  })

  if (isLoading) return <p className="px-8 py-10 text-slate-500">Loading...</p>

  return (
    <div className="mx-auto max-w-3xl px-8 py-10">
      <h1 className="mb-6 text-xl font-semibold">My Applications</h1>
      <div className="space-y-3">
        {data?.map((app) => (
          <div key={app.id} className="flex items-center justify-between rounded-lg border bg-white p-5 shadow-sm">
            <div>
              <Link to={`/jobs/${app.job.slug}`} className="font-medium hover:underline">
                {app.job.title}
              </Link>
              <p className="text-sm text-slate-600">{app.job.company}</p>
            </div>
            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs capitalize text-slate-700">
              {app.status}
            </span>
          </div>
        ))}
        {data?.length === 0 && <p className="text-slate-500">You have not applied to any jobs yet.</p>}
      </div>
    </div>
  )
}