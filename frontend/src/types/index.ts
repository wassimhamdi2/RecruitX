export interface User {
  id: number
  name: string
  email: string
  phone: string | null
  avatar: string | null
  roles: string[]
  permissions: string[]
  created_at: string
}

export interface AuthResponse {
  token: string
  user: User
}