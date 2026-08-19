import { useNavigate } from 'react-router-dom'
import type { ReactNode } from 'react'
import { useAuth } from '../store/auth'
import { Button } from './ui'

export default function AppLayout({ children }: { children: ReactNode }) {
  const user = useAuth((s) => s.user)
  const logout = useAuth((s) => s.logout)
  const navigate = useNavigate()
  const isStaff = user?.roles.some((r) => r === 'recruiter' || r === 'admin')

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const nav = [
    { label: 'Jobs', to: '/jobs', show: true },
    { label: 'My Applications', to: '/applications', show: true },
    { label: 'Applications', to: '/recruiter/applications', show: isStaff },
  ].filter((n) => n.show)

  return (
    <div className="min-h-screen">
      <header className="sticky top-0 z-10 border-b border-white/60 bg-white/60 backdrop-blur-md">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-3">
          <button
            type="button"
            onClick={() => navigate('/')}
            className="font-display text-lg font-semibold text-foreground"
          >
            RecruitX
          </button>
          <nav className="flex items-center gap-1">
            {nav.map((n) => (
              <button
                key={n.to}
                type="button"
                onClick={() => navigate(n.to)}
                className="rounded-lg px-3 py-2 text-sm font-medium text-foreground/70 transition-colors hover:bg-white/70 hover:text-foreground"
              >
                {n.label}
              </button>
            ))}
            <Button variant="ghost" onClick={handleLogout}>
              Logout
            </Button>
          </nav>
        </div>
      </header>
      <main className="mx-auto max-w-6xl px-6">{children}</main>
    </div>
  )
}