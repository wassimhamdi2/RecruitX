import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('recruitx-token')
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
  company: string
  location: string
  employment_type: string
  work_mode: string
  salary_min: string | null
  salary_max: string | null
  currency: string
  experience_min: number | null
  experience_max: number | null
  closing_date: string | null
  skills: { name: string; required_level: string | null; is_required: boolean }[]
}

export interface Application {
  id: number
  status: string
  applied_at: string
  candidate?: {
    id: number
    name: string
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

export const listJobs = (params?: Record<string, string>) => api.get<{ data: Job[] }>('/jobs', { params })
export const getJob = (slug: string) => api.get<{ data: Job }>(`/jobs/${slug}`)
export const applyToJob = (id: number) => api.post(`/jobs/${id}/apply`)
export const myApplications = () => api.get<{ data: Application[] }>('/applications')
export const recruiterApplications = (params?: Record<string, string>) =>
  api.get<{ data: Application[] }>('/recruiter/applications', { params })
export const changeApplicationStatus = (id: number, status: string, comment?: string) =>
  api.patch<{ data: Application }>(`/applications/${id}/status`, { status, comment })

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