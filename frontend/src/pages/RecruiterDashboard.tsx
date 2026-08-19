import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { useAuth } from '../store/auth'
import { staffDashboard } from '../services/api'
import AppLayout from '../components/AppLayout'

const STATUS_LABELS: Record<string, string> = {
  applied: 'Applied',
  screening: 'Screening',
  interview: 'Interview',
  offer: 'Offer',
  hired: 'Hired',
  rejected: 'Rejected',
}

export default function RecruiterDashboard() {
  const user = useAuth((s) => s.user)
  const isStaff = user?.roles.some((r) => r === 'recruiter' || r === 'admin')

  const { data, isLoading, isError } = useQuery({
    queryKey: ['staff-dashboard'],
    queryFn: () => staffDashboard().then((r) => r.data.data),
  })

  if (!isStaff) return null
  if (isLoading) return <AppLayout><p className="py-16 text-center text-foreground/60">Loading…</p></AppLayout>
  if (isError) return <AppLayout><p className="py-16 text-center text-foreground/60">Failed to load dashboard.</p></AppLayout>
  if (!data) return null

  const t = data.totals
  const maxDay = Math.max(1, ...data.applications_last_14_days.map((d) => d.count))
  const maxStatus = Math.max(1, ...Object.values(data.applications_by_status).map(Number))
  const maxJob = Math.max(1, ...data.top_jobs.map((j) => j.applications_count))

  const statCards = [
    { label: 'Total Jobs', value: t.jobs, to: '/jobs' },
    { label: 'Published Jobs', value: t.published_jobs, to: '/jobs' },
    { label: 'Applications', value: t.applications, to: '/recruiter/applications' },
    { label: 'Interviews', value: t.interviews, to: '/recruiter/interviews' },
    { label: 'Completed Interviews', value: t.completed_interviews, to: '/recruiter/interviews' },
    { label: 'Evaluations', value: t.evaluations, to: '/recruiter/evaluations' },
    {
      label: 'Avg Score',
      value: t.avg_evaluation_score === null ? '—' : `${t.avg_evaluation_score} / 10`,
      to: '/recruiter/evaluations',
    },
  ]

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">Pipeline Dashboard</h1>

        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {statCards.map((c) => (
            <Link
              key={c.label}
              to={c.to}
              className="rounded-xl border border-white/60 bg-white/70 p-5 shadow-sm backdrop-blur-md transition-colors hover:border-secondary hover:bg-white"
            >
              <p className="text-sm text-foreground/60">{c.label}</p>
              <p className="font-display mt-1 text-2xl font-semibold text-primary">{c.value}</p>
            </Link>
          ))}
        </div>

        <div className="mt-6 grid gap-4 lg:grid-cols-2">
          <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md">
            <h2 className="font-display text-lg font-semibold">Applications — last 14 days</h2>
            <div className="mt-4 flex h-40 items-end gap-1.5">
              {data.applications_last_14_days.map((d) => (
                <div key={d.date} className="group flex flex-1 flex-col items-center gap-1" title={`${d.date}: ${d.count}`}>
                  <span className="text-xs font-medium text-primary">{d.count || ''}</span>
                  <div
                    className="w-full rounded-t bg-primary/70 transition-colors group-hover:bg-primary"
                    style={{ height: `${(d.count / maxDay) * 100}%`, minHeight: d.count ? '4px' : '2px' }}
                  />
                  <span className="text-[10px] text-foreground/40">{d.date.slice(5)}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md">
            <h2 className="font-display text-lg font-semibold">Applications by status</h2>
            <div className="mt-4 space-y-2">
              {Object.entries(data.applications_by_status).map(([status, count]) => (
                <div key={status} className="flex items-center gap-3">
                  <span className="w-24 text-sm capitalize text-foreground/70">{STATUS_LABELS[status] ?? status}</span>
                  <div className="h-4 flex-1 overflow-hidden rounded-full bg-slate-100">
                    <div className="h-full rounded-full bg-secondary" style={{ width: `${(count / maxStatus) * 100}%` }} />
                  </div>
                  <span className="w-8 text-right text-sm font-medium text-foreground">{count}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md lg:col-span-2">
            <h2 className="font-display text-lg font-semibold">Top jobs by applications</h2>
            <div className="mt-4 space-y-3">
              {data.top_jobs.map((j) => (
                <div key={j.id} className="flex items-center gap-3">
                  <Link to={`/jobs/${j.slug}`} className="w-64 truncate text-sm font-medium text-foreground hover:text-primary">
                    {j.title}
                  </Link>
                  <div className="h-3 flex-1 overflow-hidden rounded-full bg-slate-100">
                    <div className="h-full rounded-full bg-accent" style={{ width: `${(j.applications_count / maxJob) * 100}%` }} />
                  </div>
                  <span className="w-8 text-right text-sm font-medium text-foreground">{j.applications_count}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  )
}