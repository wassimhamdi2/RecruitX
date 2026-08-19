import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import Chart from 'react-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { useAuth } from '../store/auth'
import { staffDashboard } from '../services/api'
import AppLayout from '../components/AppLayout'

const STATUS_LABELS: Record<string, string> = {
  applied: 'Applied',
  screening: 'Screening',
  shortlisted: 'Shortlisted',
  interview: 'Interview',
  evaluation: 'Evaluation',
  offer: 'Offer',
  hired: 'Hired',
  rejected: 'Rejected',
  withdrawn: 'Withdrawn',
}

const AXIS_LABEL: ApexOptions['xaxis'] = { labels: { style: { colors: '#64748b', fontSize: '11px' } } }

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

  const trend = {
    series: [{ name: 'Applications', data: data.applications_last_14_days.map((d) => d.count) }],
    options: {
      chart: { type: 'area', toolbar: { show: false }, fontFamily: 'inherit' },
      colors: ['#0369A1'],
      stroke: { curve: 'smooth', width: 2 },
      fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
      xaxis: { ...AXIS_LABEL, categories: data.applications_last_14_days.map((d) => d.date.slice(5)) },
      yaxis: AXIS_LABEL as ApexOptions['yaxis'],
      dataLabels: { enabled: false },
      grid: { borderColor: '#e2e8f0' },
    } as ApexOptions,
  }

  const byStatus = {
    series: Object.values(data.applications_by_status).map(Number),
    options: {
      chart: { type: 'donut', fontFamily: 'inherit' },
      labels: Object.keys(data.applications_by_status).map((s) => STATUS_LABELS[s] ?? s),
      colors: ['#0369A1', '#0EA5E9', '#16A34A', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b', '#06b6d4', '#ec4899'],
      legend: { position: 'bottom', fontSize: '13px' },
      dataLabels: { enabled: false },
      plotOptions: {
        pie: {
          donut: {
            labels: { show: true, total: { show: true, label: 'Total', fontSize: '14px', color: '#0f172a' } },
          },
        },
      },
      responsive: [{ breakpoint: 480, options: { legend: { position: 'bottom' } } }],
    } as ApexOptions,
  }

  const topJobs = {
    series: [{ name: 'Applications', data: data.top_jobs.map((j) => j.applications_count) }],
    options: {
      chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
      plotOptions: { bar: { horizontal: true, barHeight: '55%' } },
      colors: ['#16A34A'],
      xaxis: { ...AXIS_LABEL, categories: data.top_jobs.map((j) => j.title) },
      yaxis: { labels: { style: { colors: '#64748b', fontSize: '12px' } } },
      dataLabels: { enabled: false },
      grid: { borderColor: '#e2e8f0' },
    } as ApexOptions,
  }

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
            <div className="mt-4">
              <Chart type="area" height={260} options={trend.options} series={trend.series} />
            </div>
          </div>

          <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md">
            <h2 className="font-display text-lg font-semibold">Applications by status</h2>
            <div className="mt-4">
              <Chart type="donut" height={260} options={byStatus.options} series={byStatus.series} />
            </div>
          </div>

          <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md lg:col-span-2">
            <h2 className="font-display text-lg font-semibold">Top jobs by applications</h2>
            <div className="mt-4">
              <Chart type="bar" height={Math.max(220, data.top_jobs.length * 52)} options={topJobs.options} series={topJobs.series} />
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  )
}