import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import api from '../services/api'
import type { AuthResponse, User } from '../types'

interface AuthState {
  token: string | null
  user: User | null
  setAuth: (auth: AuthResponse) => void
  logout: () => void
}

export const useAuth = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      setAuth: (auth) => set({ token: auth.token, user: auth.user }),
      logout: async () => {
        try {
          await api.post('/auth/logout')
        } finally {
          set({ token: null, user: null })
        }
      },
    }),
    {
      name: 'recruitx-auth',
      partialize: (state) => ({ token: state.token, user: state.user }),
    },
  ),
)