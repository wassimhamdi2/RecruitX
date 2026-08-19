import { useNavigate } from 'react-router-dom'
import { useEffect, useRef, useState, type ReactNode } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '../store/auth'
import { myNotifications, readAllNotifications, readNotification, unreadNotificationCount, type AppNotification } from '../services/api'
import { Button } from './ui'

export default function AppLayout({ children }: { children: ReactNode }) {
  const user = useAuth((s) => s.user)
  const logout = useAuth((s) => s.logout)
  const navigate = useNavigate()
  const isStaff = user?.roles.some((r) => r === 'recruiter' || r === 'admin')
  const isAdmin = user?.roles.includes('admin')

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const nav = [
    { label: 'Jobs', to: '/jobs', show: true },
    { label: 'My Applications', to: '/applications', show: !isStaff },
    { label: 'My Interviews', to: '/me/interviews', show: !isStaff },
    { label: 'Profile', to: '/profile', show: !isStaff },
    { label: 'Dashboard', to: '/recruiter/dashboard', show: isStaff },
    { label: 'Applications', to: '/recruiter/applications', show: isStaff },
    { label: 'Interviews', to: '/recruiter/interviews', show: isStaff },
    { label: 'Evaluations', to: '/recruiter/evaluations', show: isStaff },
    { label: 'Admin', to: '/admin', show: isAdmin },
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
            <NotificationBell isStaff={isStaff ?? false} />
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

function NotificationBell({ isStaff }: { isStaff: boolean }) {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  const navigate = useNavigate()
  const qc = useQueryClient()

  const notifications = useQuery({
    queryKey: ['notifications'],
    queryFn: () => myNotifications().then((r) => r.data.data),
    refetchInterval: 30000,
  })
  const unread = useQuery({
    queryKey: ['notifications-unread'],
    queryFn: () => unreadNotificationCount().then((r) => r.data.count),
    refetchInterval: 30000,
  })

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [])

  const markAllRead = async () => {
    await readAllNotifications()
    await Promise.all([qc.invalidateQueries({ queryKey: ['notifications'] }), qc.invalidateQueries({ queryKey: ['notifications-unread'] })])
  }

  const openNotification = async (id: string, data: AppNotification['data']) => {
    await readNotification(id)
    await Promise.all([qc.invalidateQueries({ queryKey: ['notifications'] }), qc.invalidateQueries({ queryKey: ['notifications-unread'] })])
    setOpen(false)
    if (data.interview_id) navigate(isStaff ? '/recruiter/interviews' : '/me/interviews')
    else if (data.application_id) navigate(isStaff ? '/recruiter/applications' : '/applications')
  }

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="relative rounded-lg px-3 py-2 text-lg leading-none text-foreground/70 transition-colors hover:bg-white/70 hover:text-foreground"
        aria-label="Notifications"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
          <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
          <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </svg>
        {unread.data ? (
          <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-semibold text-white">
            {unread.data > 9 ? '9+' : unread.data}
          </span>
        ) : null}
      </button>
      {open ? (
        <div className="absolute right-0 top-full mt-2 w-80 overflow-hidden rounded-xl border border-white/60 bg-white shadow-lg">
          <div className="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
            <span className="text-sm font-semibold">Notifications</span>
            <button type="button" onClick={markAllRead} className="text-xs text-primary hover:underline">
              Mark all read
            </button>
          </div>
          <div className="max-h-80 overflow-y-auto">
            {notifications.data?.length ? (
              notifications.data.map((n) => (
                <button
                  key={n.id}
                  type="button"
                  onClick={() => openNotification(n.id, n.data)}
                  className={`block w-full border-b border-slate-50 px-4 py-3 text-left text-sm transition-colors hover:bg-slate-50 ${n.read_at ? 'text-foreground/60' : 'bg-sky-50 text-foreground'}`}
                >
                  <p className="line-clamp-2">{n.data.message ?? n.type}</p>
                  <p className="mt-0.5 text-xs text-foreground/50">{new Date(n.created_at).toLocaleString()}</p>
                </button>
              ))
            ) : (
              <p className="px-4 py-6 text-center text-sm text-foreground/50">No notifications</p>
            )}
          </div>
        </div>
      ) : null}
    </div>
  )
}