import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import {
  APPLICATION_STATUSES,
  changeApplicationStatus,
  recruiterApplications,
} from '../services/api'
import type { Application } from '../services/api'

const STATUS_BADGES: Record<string, string> = {
  applied: 'bg-slate-100 text-slate-700',
  screening: 'bg-yellow-100 text-yellow-700',
  shortlisted: 'bg-blue-100 text-blue-700',
  interview: 'bg-purple-100 text-purple-700',
  evaluation: 'bg-indigo-100 text-indigo-700',
  offer: 'bg-orange-100 text-orange-700',
  hired: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  withdrawn: 'bg-gray-200 text-gray-600',
}

export default function RecruiterApplications() {
  const [statusFilter, setStatusFilter] = useState('')
  const queryClient = useQueryClient()
  const { data, isLoading } = useQuery({
    queryKey: ['recruiter-applications', statusFilter],
    queryFn: () =>
      recruiterApplications(statusFilter ? { status: statusFilter } : {}).then((r) => r.data.data),
  })

  const changeStatus = async (app: Application, status: string) => {
    await changeApplicationStatus(app.id, status)
    queryClient.invalidateQueries({ queryKey: ['recruiter-applications'] })
  }

  return (
    <div className="mx-auto max-w-4xl px-8 py-10">
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-xl font-semibold">Applications</h1>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="rounded border px-3 py-2 text-sm"
        >
          <option value="">All statuses</option>
          {APPLICATION_STATUSES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
      </div>

      {isLoading && <p className="text-slate-500">Loading...</p>}

      <div className="space-y-3">
        {data?.map((app) => (
          <div key={app.id} className="rounded-lg border bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium">{app.candidate?.name}</p>
                <p className="text-sm text-slate-600">
                  {app.job.title} · {app.job.company}
                </p>
              </div>
              <span
                className={`rounded-full px-3 py-1 text-xs capitalize ${STATUS_BADGES[app.status] ?? 'bg-slate-100'}`}
              >
                {app.status}
              </span>
            </div>
            <select
              defaultValue=""
              onChange={(e) => e.target.value && changeStatus(app, e.target.value)}
              className="mt-3 rounded border px-3 py-1.5 text-sm"
            >
              <option value="" disabled>
                Move to...
              </option>
              {APPLICATION_STATUSES.filter((s) => s !== app.status).map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </select>
          </div>
        ))}
        {data?.length === 0 && <p className="text-slate-500">No applications.</p>}
      </div>
    </div>
  )
}