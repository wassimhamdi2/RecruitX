import { useNavigate } from 'react-router-dom'
import { useAuth } from '../store/auth'
import AppLayout from '../components/AppLayout'

export default function Dashboard() {
  const user = useAuth((s) => s.user)
  const navigate = useNavigate()
  const isStaff = user?.roles.some((r) => r === 'recruiter' || r === 'admin')

  const cards = isStaff
    ? [
        {
          title: 'Applications',
          subtitle: 'Review candidates and move them through the pipeline',
          to: '/recruiter/applications',
        },
        { title: 'Browse Jobs', subtitle: 'View published job offers', to: '/jobs' },
      ]
    : [
        {
          title: 'Browse Jobs',
          subtitle: 'Search and apply to open positions',
          to: '/jobs',
        },
        {
          title: 'My Applications',
          subtitle: 'Track the status of your applications',
          to: '/applications',
        },
      ]

  return (
    <AppLayout>
      <div className="py-10">
        <div className="mb-8 rounded-2xl border border-white/60 bg-white/60 p-8 shadow-sm backdrop-blur-md">
          <p className="text-sm font-medium text-primary">Welcome back</p>
          <h1 className="font-display mt-1 text-3xl font-semibold">{user?.name}</h1>
          <p className="mt-1 text-sm text-foreground/70">{user?.email}</p>
          <div className="mt-4 flex gap-2">
            {user?.roles.map((role) => (
              <span key={role} className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium capitalize text-primary">
                {role}
              </span>
            ))}
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          {cards.map((c) => (
            <button
              key={c.to}
              type="button"
              onClick={() => navigate(c.to)}
              className="rounded-xl border border-white/60 bg-white/70 p-6 text-left shadow-sm backdrop-blur-md transition-colors hover:border-secondary hover:bg-white"
            >
              <h2 className="font-display text-xl font-semibold text-primary">{c.title}</h2>
              <p className="mt-1 text-sm text-foreground/70">{c.subtitle}</p>
            </button>
          ))}
        </div>
      </div>
    </AppLayout>
  )
}