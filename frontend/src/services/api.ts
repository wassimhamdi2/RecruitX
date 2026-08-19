import axios from 'axios'
import { useAuth } from '../store/auth'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = useAuth.getState().token
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export interface Job {
  id: number
  title: string
  slug: string
  description: string
  requirements: string | null
  responsibilities: string | null
  company: string
  company_id: number
  location: string
  employment_type: string
  work_mode: string
  salary_min: string | null
  salary_max: string | null
  currency: string
  experience_min: number | null
  experience_max: number | null
  closing_date: string | null
  status?: string
  published_at?: string | null
  skills: { name: string; required_level: string | null; is_required: boolean }[]
}

export interface Application {
  id: number
  status: string
  applied_at: string
  candidate?: {
    id: number
    name: string
    has_cv: boolean
  } | null
  job: {
    id: number
    title: string
    slug: string
    company: string | null
    location: string
    employment_type: string
    work_mode: string
  }
}

export interface CandidateProfile {
  id: number
  first_name: string
  last_name: string
  phone: string | null
  date_of_birth: string | null
  address: string | null
  city: string | null
  country: string | null
  linkedin_url: string | null
  github_url: string | null
  portfolio_url: string | null
  bio: string | null
  years_of_experience: number | null
  availability: string | null
  expected_salary: string | null
}

export interface Interview {
  id: number
  application_id: number
  type: string
  status: string
  scheduled_at: string
  duration: number
  location: string | null
  meeting_url: string | null
  notes: string | null
  candidate?: {
    id: number
    name: string
  } | null
  job?: {
    title: string
    company: string | null
  } | null
  participants?: { name: string | null; role: string | null }[]
}

export interface ScheduleInterviewInput {
  type: string
  scheduled_at: string
  duration?: number
  location?: string
  meeting_url?: string
  notes?: string
}

export const listJobs = (params?: Record<string, string>) => api.get<{ data: Job[] }>('/jobs', { params })
export const getJob = (slug: string) => api.get<{ data: Job }>(`/jobs/${slug}`)
export const applyToJob = (id: number) => api.post(`/jobs/${id}/apply`)
export const myJobs = () => api.get<{ data: Job[] }>('/recruiter/jobs')
export const createJob = (data: Record<string, unknown>) => api.post<{ data: Job }>('/jobs', data)
export const updateJob = (id: number, data: Record<string, unknown>) => api.patch<{ data: Job }>(`/jobs/${id}`, data)
export const deleteJob = (id: number) => api.delete(`/jobs/${id}`)
export const companies = () => api.get<{ data: { id: number; name: string }[] }>('/companies')
export const myApplications = () => api.get<{ data: Application[] }>('/applications')
export const recruiterApplications = (params?: Record<string, string>) =>
  api.get<{ data: Application[] }>('/recruiter/applications', { params })
export const changeApplicationStatus = (id: number, status: string, comment?: string) =>
  api.patch<{ data: Application }>(`/applications/${id}/status`, { status, comment })

export const getMyProfile = () => api.get<{ data: CandidateProfile }>('/me/profile')
export const updateMyProfile = (data: Partial<CandidateProfile>) =>
  api.put<{ data: CandidateProfile }>('/me/profile', data)

export const uploadCv = (file: File) => {
  const form = new FormData()
  form.append('cv', file)
  return api.post<{ data: { has_cv: boolean; file_name: string } }>('/me/cv', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

const downloadBlob = (url: string, filename: string) => {
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
}

export const downloadOwnCv = async () => {
  const { data } = await api.get<Blob>('/me/cv', { responseType: 'blob' })
  downloadBlob(URL.createObjectURL(data), 'cv.pdf')
}

export const downloadApplicationCv = async (applicationId: number) => {
  const { data } = await api.get<Blob>(`/applications/${applicationId}/cv`, { responseType: 'blob' })
  downloadBlob(URL.createObjectURL(data), 'cv.pdf')
}

export const scheduleInterview = (applicationId: number, input: ScheduleInterviewInput) =>
  api.post<{ data: Interview }>(`/applications/${applicationId}/interviews`, input)
export const recruiterInterviews = (params?: Record<string, string>) =>
  api.get<{ data: Interview[] }>('/recruiter/interviews', { params })
export const myInterviews = () => api.get<{ data: Interview[] }>('/me/interviews')
export const updateInterview = (
  id: number,
  input: { status?: string; scheduled_at?: string; notes?: string },
) => api.patch<{ data: Interview }>(`/interviews/${id}`, input)

export const INTERVIEW_TYPES = ['phone', 'video', 'onsite', 'technical', 'hr', 'final'] as const

export interface Evaluation {
  id: number
  overall_score: string | null
  recommendation: string | null
  comments: string | null
  created_at: string
  candidate?: { id: number; name: string } | null
  job?: { title: string; company: string | null } | null
  interview?: { id: number; type: string; scheduled_at: string; status: string } | null
  evaluator?: string | null
  scores?: { criterion: string; max_score: number; score: number; comment: string | null }[]
}

export interface EvaluationCriterion {
  id: number
  name: string
  max_score: number
  weight: number
}

export const createEvaluation = (
  applicationId: number,
  interviewId: number,
  input: {
    recommendation: string
    comments?: string
    scores: { criterion_id: number; score: number; comment?: string }[]
  },
) => api.post<{ data: Evaluation }>(`/applications/${applicationId}/interviews/${interviewId}/evaluation`, input)
export const recruiterEvaluations = (params?: Record<string, string>) =>
  api.get<{ data: Evaluation[] }>('/recruiter/evaluations', { params })
export const getEvaluation = (id: number) => api.get<{ data: Evaluation }>(`/evaluations/${id}`)
export interface AppNotification {
  id: string
  type: string
  data: { message?: string; job_title?: string; application_id?: number; interview_id?: number }
  read_at: string | null
  created_at: string
}

export const myNotifications = () => api.get<{ data: AppNotification[] }>('/me/notifications')
export const unreadNotificationCount = () => api.get<{ count: number }>('/me/notifications/unread-count')
export const readNotification = (id: string) => api.patch(`/me/notifications/${id}/read`)
export const readAllNotifications = () => api.post('/me/notifications/read-all')

export interface StaffDashboard {
  totals: {
    jobs: number
    published_jobs: number
    applications: number
    interviews: number
    completed_interviews: number
    evaluations: number
    avg_evaluation_score: number | null
  }
  applications_by_status: Record<string, number>
  applications_last_14_days: { date: string; count: number }[]
  top_jobs: { id: number; title: string; slug: string; applications_count: number }[]
}

export const staffDashboard = () => api.get<{ data: StaffDashboard }>('/staff/dashboard')

export interface AuditLogEntry {
  id: number
  user: { id: number; name: string; email: string } | null
  action: string
  auditable_type: string
  auditable_id: number | null
  before: Record<string, unknown> | null
  after: Record<string, unknown> | null
  ip_address: string | null
  created_at: string
}

export const adminUsers = (q = '') => api.get<{ data: { id: number; name: string; email: string; roles: string[] }[] }>('/admin/users', { params: { q } })
export const adminUpdateUserRole = (id: number, role: string) => api.patch(`/admin/users/${id}/role`, { role })
export const adminDeleteUser = (id: number) => api.delete(`/admin/users/${id}`)
export const adminCompanies = () => api.get<{ data: { id: number; name: string; city: string; country: string; job_offers_count: number }[] }>('/admin/companies')
export const adminUpdateCompany = (id: number, data: Record<string, unknown>) => api.patch(`/admin/companies/${id}`, data)
export const adminAuditLogs = () => api.get<{ data: AuditLogEntry[] }>('/admin/audit-logs')

export const evaluationCriteria = () => api.get<{ data: EvaluationCriterion[] }>('/evaluation-criteria')

export const RECOMMENDATIONS = ['strong_yes', 'yes', 'maybe', 'no', 'strong_no'] as const

export const APPLICATION_STATUSES = [
  'applied',
  'screening',
  'shortlisted',
  'interview',
  'evaluation',
  'offer',
  'hired',
  'rejected',
  'withdrawn',
] as const

export default api