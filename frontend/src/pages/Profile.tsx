import { useRef, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import type { Resolver } from 'react-hook-form'
import { downloadOwnCv, getMyProfile, updateMyProfile, uploadCv } from '../services/api'
import type { CandidateProfile } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button, Card, Input, Label } from '../components/ui'

const schema = z.object({
  phone: z.string().max(30).optional(),
  city: z.string().max(100).optional(),
  country: z.string().max(100).optional(),
  address: z.string().max(255).optional(),
  bio: z.string().max(5000).optional(),
  years_of_experience: z.coerce.number().min(0).max(60).optional(),
  availability: z.string().max(50).optional(),
  expected_salary: z.coerce.number().min(0).optional(),
  linkedin_url: z.union([z.literal(''), z.url()]).optional(),
  github_url: z.union([z.literal(''), z.url()]).optional(),
  portfolio_url: z.union([z.literal(''), z.url()]).optional(),
  date_of_birth: z.string().optional(),
})

type FormData = {
  phone?: string
  city?: string
  country?: string
  address?: string
  bio?: string
  years_of_experience?: number
  availability?: string
  expected_salary?: number
  linkedin_url?: string
  github_url?: string
  portfolio_url?: string
  date_of_birth?: string
}

const toForm = (p?: CandidateProfile): FormData => ({
  phone: p?.phone ?? '',
  city: p?.city ?? '',
  country: p?.country ?? '',
  address: p?.address ?? '',
  bio: p?.bio ?? '',
  years_of_experience: p?.years_of_experience ?? undefined,
  availability: p?.availability ?? '',
  expected_salary: p?.expected_salary ? Number(p.expected_salary) : undefined,
  linkedin_url: p?.linkedin_url ?? '',
  github_url: p?.github_url ?? '',
  portfolio_url: p?.portfolio_url ?? '',
  date_of_birth: p?.date_of_birth ?? '',
})

export default function Profile() {
  const [saved, setSaved] = useState(false)
  const [cvName, setCvName] = useState('')
  const [cvError, setCvError] = useState('')
  const fileRef = useRef<HTMLInputElement>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['profile'],
    queryFn: () => getMyProfile().then((r) => r.data.data),
  })

  const {
    register,
    handleSubmit,
    formState: { isSubmitting, errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema) as unknown as Resolver<FormData>,
    values: toForm(data),
  })

  if (isLoading) return <AppLayout><p className="py-10 text-foreground/60">Loading…</p></AppLayout>

  const onSubmit = async (formData: FormData) => {
    const payload: Partial<CandidateProfile> = {
      ...formData,
      expected_salary: formData.expected_salary?.toString() ?? null,
      years_of_experience: formData.years_of_experience ?? null,
      phone: formData.phone || null,
      city: formData.city || null,
      country: formData.country || null,
      address: formData.address || null,
      bio: formData.bio || null,
      availability: formData.availability || null,
      linkedin_url: formData.linkedin_url || null,
      github_url: formData.github_url || null,
      portfolio_url: formData.portfolio_url || null,
      date_of_birth: formData.date_of_birth || null,
    }
    await updateMyProfile(payload)
    setSaved(true)
    setTimeout(() => setSaved(false), 2000)
  }

  const handleCv = async (file: File | undefined) => {
    setCvError('')
    if (!file) return
    try {
      const { data: res } = await uploadCv(file)
      setCvName(res.data.file_name)
    } catch (err: unknown) {
      const message = (err as { response?: { data?: { errors?: Record<string, string[]> } } })
        .response?.data?.errors
      setCvError(message ? Object.values(message)[0][0] : 'Upload failed')
    }
  }

  const error = (name: keyof FormData) =>
    errors[name] && <p className="mt-1 text-sm text-destructive">{errors[name]?.message}</p>

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">My Profile</h1>

        <div className="mt-8 grid gap-6 lg:grid-cols-2">
          <Card>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div>
                <Label>Phone</Label>
                <Input type="tel" {...register('phone')} />
                {error('phone')}
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label>City</Label>
                  <Input {...register('city')} />
                  {error('city')}
                </div>
                <div>
                  <Label>Country</Label>
                  <Input {...register('country')} />
                  {error('country')}
                </div>
              </div>
              <div>
                <Label>Address</Label>
                <Input {...register('address')} />
                {error('address')}
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label>Years of experience</Label>
                  <Input type="number" min={0} {...register('years_of_experience')} />
                  {error('years_of_experience')}
                </div>
                <div>
                  <Label>Expected salary</Label>
                  <Input type="number" min={0} step="0.01" {...register('expected_salary')} />
                  {error('expected_salary')}
                </div>
              </div>
              <div>
                <Label>Availability</Label>
                <Input placeholder="e.g. immediately, in 1 month" {...register('availability')} />
                {error('availability')}
              </div>
              <div>
                <Label>LinkedIn</Label>
                <Input type="url" placeholder="https://linkedin.com/in/…" {...register('linkedin_url')} />
                {error('linkedin_url')}
              </div>
              <div>
                <Label>GitHub</Label>
                <Input type="url" placeholder="https://github.com/…" {...register('github_url')} />
                {error('github_url')}
              </div>
              <div>
                <Label>Portfolio</Label>
                <Input type="url" placeholder="https://…" {...register('portfolio_url')} />
                {error('portfolio_url')}
              </div>
              <div>
                <Label>Bio</Label>
                <textarea
                  {...register('bio')}
                  rows={4}
                  className="w-full rounded-lg border border-line bg-white/70 px-3 py-2 text-sm transition-colors focus:border-primary focus:outline-none"
                />
                {error('bio')}
              </div>
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? 'Saving…' : 'Save profile'}
              </Button>
              {saved && <span className="ml-2 text-sm text-accent">Saved</span>}
            </form>
          </Card>

          <Card>
            <h2 className="font-display text-lg font-semibold">Curriculum Vitae</h2>
            <p className="mt-1 text-sm text-foreground/70">
              PDF, DOC or DOCX — max 5 MB.
            </p>
            <div className="mt-4">
              <input
                ref={fileRef}
                type="file"
                accept=".pdf,.doc,.docx"
                className="hidden"
                onChange={(e) => handleCv(e.target.files?.[0])}
              />
              <Button variant="ghost" onClick={() => fileRef.current?.click()}>
                Upload CV
              </Button>
              <Button className="ml-2" onClick={() => downloadOwnCv()}>
                Download my CV
              </Button>
            </div>
            {cvName && <p className="mt-3 text-sm font-medium text-accent">{cvName}</p>}
            {cvError && <p className="mt-3 text-sm text-destructive">{cvError}</p>}
          </Card>
        </div>
      </div>
    </AppLayout>
  )
}