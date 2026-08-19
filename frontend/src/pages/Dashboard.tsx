import { useNavigate } from 'react-router-dom'
import { useAuth } from '../store/auth'

export default function Dashboard() {
  const user = useAuth((s) => s.user)
  const logout = useAuth((s) => s.logout)
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  return (
    <div className="min-h-screen bg-slate-100">
      <header className="flex items-center justify-between border-b bg-white px-8 py-4">
        <h1 className="text-lg font-semibold">RecruitX</h1>
        <button onClick={handleLogout} className="rounded bg-slate-200 px-4 py-2 text-sm hover:bg-slate-300">
          Logout
        </button>
      </header>

      <main className="mx-auto max-w-3xl space-y-6 px-8 py-10">
        <div className="rounded-lg bg-white p-6 shadow">
          <h2 className="text-xl font-semibold">Welcome, {user?.name}</h2>
          <p className="text-slate-600">{user?.email}</p>
          <div className="mt-3 flex gap-2">
            {user?.roles.map((role) => (
              <span key={role} className="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                {role}
              </span>
            ))}
          </div>
        </div>

        <div className="rounded-lg bg-white p-6 shadow">
          <h3 className="font-medium">Your permissions</h3>
          <div className="mt-2 flex flex-wrap gap-1.5">
            {user?.permissions.map((permission) => (
              <span key={permission} className="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600">
                {permission}
              </span>
            ))}
          </div>
        </div>
      </main>
    </div>
  )
}