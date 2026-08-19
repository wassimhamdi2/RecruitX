import { Navigate, Route, Routes } from 'react-router-dom'
import ProtectedRoute from '../components/ProtectedRoute'
import { useAuth } from '../store/auth'
import Dashboard from '../pages/Dashboard'
import Jobs from '../pages/Jobs'
import JobDetail from '../pages/JobDetail'
import Login from '../pages/Login'
import MyApplications from '../pages/MyApplications'
import Profile from '../pages/Profile'
import RecruiterApplications from '../pages/RecruiterApplications'
import Register from '../pages/Register'

export default function AppRouter() {
  const token = useAuth((s) => s.token)
  const user = useAuth((s) => s.user)
  const isStaff = user?.roles.some((r) => r === 'recruiter' || r === 'admin')

  return (
    <Routes>
      <Route path="/login" element={token ? <Navigate to="/" replace /> : <Login />} />
      <Route path="/register" element={token ? <Navigate to="/" replace /> : <Register />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Dashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/jobs"
        element={
          <ProtectedRoute>
            <Jobs />
          </ProtectedRoute>
        }
      />
      <Route
        path="/jobs/:slug"
        element={
          <ProtectedRoute>
            <JobDetail />
          </ProtectedRoute>
        }
      />
      <Route
        path="/applications"
        element={
          <ProtectedRoute>
            <MyApplications />
          </ProtectedRoute>
        }
      />
      <Route
        path="/profile"
        element={
          <ProtectedRoute>
            {!isStaff ? <Profile /> : <Navigate to="/" replace />}
          </ProtectedRoute>
        }
      />
      <Route
        path="/recruiter/applications"
        element={
          <ProtectedRoute>
            {isStaff ? <RecruiterApplications /> : <Navigate to="/" replace />}
          </ProtectedRoute>
        }
      />
    </Routes>
  )
}