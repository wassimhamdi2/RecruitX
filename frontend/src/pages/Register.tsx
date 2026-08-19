import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { Link, useNavigate } from 'react-router-dom'
import api from '../services/api'
import { useAuth } from '../store/auth'
import type { AuthResponse } from '../types'
import { Button, Card, Input, Label } from '../components/ui'

const schema = z
  .object({
    name: z.string().min(1, 'Name is required'),
    email: z.string().email('Enter a valid email'),
    first_name: z.string().min(1, 'First name is required'),
    last_name: z.string().min(1, 'Last name is required'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

type FormData = z.infer<typeof schema>

export default function Register() {
  const setAuth = useAuth((s) => s.setAuth)
  const navigate = useNavigate()
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({ resolver: zodResolver(schema) })

  const onSubmit = async (data: FormData) => {
    try {
      const { data: auth } = await api.post<AuthResponse>('/auth/register', data)
      setAuth(auth)
      navigate('/')
    } catch (err: unknown) {
      const errors = (err as { response?: { data?: Record<string, string[]> } }).response?.data
      if (errors?.email) {
        setError('email', { message: errors.email[0] })
      }
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center px-6 py-10">
      <Card className="w-full max-w-sm">
        <h1 className="font-display text-2xl font-semibold">Create your account</h1>
        <form onSubmit={handleSubmit(onSubmit)} className="mt-6 space-y-4">
          <div>
            <Label>Full name</Label>
            <Input type="text" {...register('name')} />
            {errors.name && <p className="mt-1 text-sm text-destructive">{errors.name.message}</p>}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <Label>First name</Label>
              <Input type="text" {...register('first_name')} />
              {errors.first_name && (
                <p className="mt-1 text-sm text-destructive">{errors.first_name.message}</p>
              )}
            </div>
            <div>
              <Label>Last name</Label>
              <Input type="text" {...register('last_name')} />
              {errors.last_name && (
                <p className="mt-1 text-sm text-destructive">{errors.last_name.message}</p>
              )}
            </div>
          </div>

          <div>
            <Label>Email</Label>
            <Input type="email" {...register('email')} />
            {errors.email && <p className="mt-1 text-sm text-destructive">{errors.email.message}</p>}
          </div>

          <div>
            <Label>Password</Label>
            <Input type="password" {...register('password')} />
            {errors.password && (
              <p className="mt-1 text-sm text-destructive">{errors.password.message}</p>
            )}
          </div>

          <div>
            <Label>Confirm password</Label>
            <Input type="password" {...register('password_confirmation')} />
            {errors.password_confirmation && (
              <p className="mt-1 text-sm text-destructive">{errors.password_confirmation.message}</p>
            )}
          </div>

          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting ? 'Creating account…' : 'Register'}
          </Button>
        </form>

        <p className="mt-4 text-sm text-foreground/70">
          Already have an account?{' '}
          <Link to="/login" className="font-medium text-primary hover:underline">
            Sign in
          </Link>
        </p>
      </Card>
    </div>
  )
}