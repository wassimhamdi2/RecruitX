import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '../store/auth'
import { adminAuditLogs, adminCompanies, adminUpdateCompany, adminUpdateUserRole, adminUsers, adminDeleteUser } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button } from '../components/ui'

const ROLES = ['candidate', 'recruiter', 'admin']

export default function AdminPanel() {
  const user = useAuth((s) => s.user)
  const isAdmin = user?.roles.includes('admin')
  const [tab, setTab] = useState<'users' | 'companies' | 'audit'>('users')

  if (!isAdmin) return null

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">Admin Panel</h1>
        <div className="mt-4 flex gap-2">
          {(['users', 'companies', 'audit'] as const).map((t) => (
            <button
              key={t}
              type="button"
              onClick={() => setTab(t)}
              className={`rounded-lg px-4 py-2 text-sm font-medium capitalize transition-colors ${
                tab === t ? 'bg-primary text-white' : 'bg-white/70 text-foreground/70 hover:bg-white'
              }`}
            >
              {t === 'audit' ? 'Audit Logs' : t}
            </button>
          ))}
        </div>
        <div className="mt-6">{tab === 'users' ? <Users /> : tab === 'companies' ? <Companies /> : <Audit />}</div>
      </div>
    </AppLayout>
  )
}

function Users() {
  const [q, setQ] = useState('')
  const qc = useQueryClient()
  const me = useAuth((s) => s.user)

  const users = useQuery({
    queryKey: ['admin-users', q],
    queryFn: () => adminUsers(q).then((r) => r.data.data),
  })

  const changeRole = useMutation({
    mutationFn: ({ id, role }: { id: number; role: string }) => adminUpdateUserRole(id, role),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
  })

  const remove = useMutation({
    mutationFn: (id: number) => adminDeleteUser(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
  })

  return (
    <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md">
      <div className="flex items-center justify-between">
        <h2 className="font-display text-lg font-semibold">Users</h2>
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Search name or email…"
          className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-primary focus:outline-none"
        />
      </div>
      <table className="mt-4 w-full text-left text-sm">
        <thead>
          <tr className="border-b border-slate-100 text-xs uppercase tracking-wide text-foreground/50">
            <th className="py-2 pr-4">Name</th>
            <th className="py-2 pr-4">Email</th>
            <th className="py-2 pr-4">Role</th>
            <th className="py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          {users.data?.map((u) => (
            <tr key={u.id} className="border-b border-slate-50">
              <td className="py-2.5 pr-4 font-medium">{u.name}</td>
              <td className="py-2.5 pr-4 text-foreground/70">{u.email}</td>
              <td className="py-2.5 pr-4">
                <select
                  value={u.roles[0] ?? ''}
                  disabled={u.id === me?.id}
                  onChange={(e) => changeRole.mutate({ id: u.id, role: e.target.value })}
                  className="rounded-lg border border-slate-200 px-2 py-1 text-sm capitalize focus:border-primary focus:outline-none disabled:opacity-50"
                >
                  {ROLES.map((r) => (
                    <option key={r} value={r} className="capitalize">
                      {r}
                    </option>
                  ))}
                </select>
              </td>
              <td className="py-2.5">
                <button
                  type="button"
                  disabled={u.id === me?.id}
                  onClick={() => {
                    if (confirm(`Delete user ${u.name}?`)) remove.mutate(u.id)
                  }}
                  className="text-sm text-red-600 hover:underline disabled:opacity-40"
                >
                  Delete
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

function Companies() {
  const qc = useQueryClient()

  const companies = useQuery({
    queryKey: ['admin-companies'],
    queryFn: () => adminCompanies().then((r) => r.data.data),
  })

  const update = useMutation({
    mutationFn: ({ id, name, city, country }: { id: number; name: string; city: string; country: string }) =>
      adminUpdateCompany(id, { name, city, country }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-companies'] }),
  })

  return (
    <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md">
      <h2 className="font-display text-lg font-semibold">Companies</h2>
      <table className="mt-4 w-full text-left text-sm">
        <thead>
          <tr className="border-b border-slate-100 text-xs uppercase tracking-wide text-foreground/50">
            <th className="py-2 pr-4">Name</th>
            <th className="py-2 pr-4">City</th>
            <th className="py-2 pr-4">Country</th>
            <th className="py-2 pr-4">Jobs</th>
            <th className="py-2">Edit</th>
          </tr>
        </thead>
        <tbody>
          {companies.data?.map((c) => (
            <CompanyRow key={c.id} company={c} onSave={(d) => update.mutate({ id: c.id, ...d })} />
          ))}
        </tbody>
      </table>
    </div>
  )
}

function CompanyRow({
  company,
  onSave,
}: {
  company: { id: number; name: string; city: string; country: string; job_offers_count: number }
  onSave: (d: { name: string; city: string; country: string }) => void
}) {
  const [editing, setEditing] = useState(false)
  const [name, setName] = useState(company.name)
  const [city, setCity] = useState(company.city ?? '')
  const [country, setCountry] = useState(company.country ?? '')

  return (
    <tr className="border-b border-slate-50">
      <td className="py-2.5 pr-4">
        {editing ? (
          <input value={name} onChange={(e) => setName(e.target.value)} className="rounded-lg border border-slate-200 px-2 py-1 text-sm" />
        ) : (
          <span className="font-medium">{company.name}</span>
        )}
      </td>
      <td className="py-2.5 pr-4">
        {editing ? (
          <input value={city} onChange={(e) => setCity(e.target.value)} className="rounded-lg border border-slate-200 px-2 py-1 text-sm" />
        ) : (
          company.city ?? '—'
        )}
      </td>
      <td className="py-2.5 pr-4">
        {editing ? (
          <input value={country} onChange={(e) => setCountry(e.target.value)} className="rounded-lg border border-slate-200 px-2 py-1 text-sm" />
        ) : (
          company.country ?? '—'
        )}
      </td>
      <td className="py-2.5 pr-4">{company.job_offers_count}</td>
      <td className="py-2.5">
        {editing ? (
          <div className="flex gap-2">
            <Button
              onClick={() => {
                onSave({ name, city, country })
                setEditing(false)
              }}
            >
              Save
            </Button>
            <Button variant="ghost" onClick={() => setEditing(false)}>
              Cancel
            </Button>
          </div>
        ) : (
          <button type="button" onClick={() => setEditing(true)} className="text-sm text-primary hover:underline">
            Edit
          </button>
        )}
      </td>
    </tr>
  )
}

function Audit() {
  const logs = useQuery({
    queryKey: ['admin-audit'],
    queryFn: () => adminAuditLogs().then((r) => r.data.data),
  })

  return (
    <div className="rounded-xl border border-white/60 bg-white/70 p-6 shadow-sm backdrop-blur-md">
      <h2 className="font-display text-lg font-semibold">Audit Logs</h2>
      <div className="mt-4 overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-slate-100 text-xs uppercase tracking-wide text-foreground/50">
              <th className="py-2 pr-4">When</th>
              <th className="py-2 pr-4">User</th>
              <th className="py-2 pr-4">Action</th>
              <th className="py-2 pr-4">Subject</th>
              <th className="py-2">Details</th>
            </tr>
          </thead>
          <tbody>
            {logs.data?.map((l) => (
              <tr key={l.id} className="border-b border-slate-50 align-top">
                <td className="py-2.5 pr-4 whitespace-nowrap text-foreground/70">{new Date(l.created_at).toLocaleString()}</td>
                <td className="py-2.5 pr-4">{l.user ? `${l.user.name} (${l.user.email})` : '—'}</td>
                <td className="py-2.5 pr-4">
                  <span className="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">{l.action}</span>
                </td>
                <td className="py-2.5 pr-4 text-foreground/70">
                  {l.auditable_type ? `${l.auditable_type} #${l.auditable_id}` : '—'}
                </td>
                <td className="py-2.5 font-mono text-xs text-foreground/60">
                  {l.before || l.after ? (
                    <pre className="max-w-md truncate whitespace-pre-wrap">
                      {JSON.stringify({ before: l.before, after: l.after })}
                    </pre>
                  ) : (
                    '—'
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}