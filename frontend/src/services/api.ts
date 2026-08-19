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

export const listJobs = (params?: Record<string, string>) => api.get<{ data: Job[] }>('/jobs', { params })
export const getJob = (slug: string) => api.get<{ data: Job }>(`/jobs/${slug}`)
export const applyToJob = (id: number) => api.post(`/jobs/${id}/apply`)
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