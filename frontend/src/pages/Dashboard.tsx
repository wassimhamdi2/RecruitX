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
        <nav className="flex gap-4 text-sm">
          <button onClick={() => navigate('/jobs')} className="hover:underline">
            Jobs
          </button>
          <button onClick={() => navigate('/applications')} className="hover:underline">
            My Applications
          </button>
          <button onClick={handleLogout} className="rounded bg-slate-200 px-3 py-1 hover:bg-slate-300">
            Logout
          </button>
        </nav>
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

        <div className="grid grid-cols-2 gap-4">
          <button
            onClick={() => navigate('/jobs')}
            className="rounded-lg bg-white p-6 text-left shadow hover:bg-blue-50"
          >
            <div className="text-2xl font-semibold text-blue-600">Browse Jobs</div>
            <p className="mt-1 text-sm text-slate-600">Search and apply to open positions</p>
          </button>
          <button
            onClick={() => navigate('/applications')}
            className="rounded-lg bg-white p-6 text-left shadow hover:bg-blue-50"
          >
            <div className="text-2xl font-semibold text-blue-600">My Applications</div>
            <p className="mt-1 text-sm text-slate-600">Track the status of your applications</p>
          </button>
        </div>
      </main>
    </div>
  )
}