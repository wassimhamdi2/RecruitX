import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import {
  INTERVIEW_TYPES,
  recruiterApplications,
  recruiterInterviewers,
  recruiterInterviews,
  scheduleInterview,
  updateInterview,
} from '../services/api'
import type { Interview, ScheduleInterviewInput } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button, Card, Input, Label, Select, StatusBadge } from '../components/ui'

type ScheduleForm = ScheduleInterviewInput & { application_id: string }

export default function RecruiterInterviews() {
  const [rescheduling, setRescheduling] = useState<number | null>(null)
  const [rescheduleTime, setRescheduleTime] = useState('')
  const [error, setError] = useState('')
  const [interviewers, setInterviewers] = useState<number[]>([])
  const queryClient = useQueryClient()

  const { register, handleSubmit, reset } = useForm<ScheduleForm>()
  const applications = useQuery({
    queryKey: ['recruiter-applications'],
    queryFn: () => recruiterApplications().then((r) => r.data.data),
  })
  const interviews = useQuery({
    queryKey: ['recruiter-interviews'],
    queryFn: () => recruiterInterviews().then((r) => r.data.data),
  })
  const staff = useQuery({
    queryKey: ['recruiter-interviewers'],
    queryFn: () => recruiterInterviewers().then((r) => r.data.data),
  })

  const onSubmit = async (data: ScheduleForm) => {
    setError('')
    if (!data.application_id) return
    try {
      await scheduleInterview(Number(data.application_id), {
        type: data.type,
        scheduled_at: new Date(data.scheduled_at).toISOString(),
        duration: data.duration,
        location: data.location || undefined,
        meeting_url: data.meeting_url || undefined,
        notes: data.notes || undefined,
        interviewers,
      })
      reset()
      setInterviewers([])
      queryClient.invalidateQueries({ queryKey: ['recruiter-interviews'] })
    } catch {
      setError('Could not schedule interview.')
    }
  }

  const move = async (id: number, status: string) => {
    await updateInterview(id, { status })
    queryClient.invalidateQueries({ queryKey: ['recruiter-interviews'] })
  }

  const doReschedule = async (id: number) => {
    if (!rescheduleTime) return
    await updateInterview(id, { status: 'rescheduled', scheduled_at: new Date(rescheduleTime).toISOString() })
    setRescheduling(null)
    setRescheduleTime('')
    queryClient.invalidateQueries({ queryKey: ['recruiter-interviews'] })
  }

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">Interviews</h1>

        <div className="mt-8 grid gap-6 lg:grid-cols-2">
          <Card>
            <h2 className="font-display text-lg font-semibold">Schedule interview</h2>
            <form onSubmit={handleSubmit(onSubmit)} className="mt-4 space-y-4">
              <div>
                <Label>Application</Label>
                <Select className="w-full" {...register('application_id', { required: true })}>
                  <option value="" disabled>
                    Select candidate…
                  </option>
                  {applications.data?.map((app) => (
                    <option key={app.id} value={app.id}>
                      {app.candidate?.name} — {app.job.title}
                    </option>
                  ))}
                </Select>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label>Type</Label>
                  <Select className="w-full" {...register('type', { required: true })}>
                    <option value="" disabled>
                      Type…
                    </option>
                    {INTERVIEW_TYPES.map((t) => (
                      <option key={t} value={t}>
                        {t}
                      </option>
                    ))}
                  </Select>
                </div>
                <div>
                  <Label>Duration (min)</Label>
                  <Input type="number" min={30} max={480} defaultValue={60} {...register('duration')} />
                </div>
              </div>
              <div>
                <Label>Scheduled at</Label>
                <Input type="datetime-local" required {...register('scheduled_at')} />
              </div>
              <div>
                <Label>Interviewers</Label>
                <div className="flex flex-wrap gap-2">
                  {staff.data?.map((s) => (
                    <label
                      key={s.id}
                      className={`cursor-pointer rounded-full border px-3 py-1 text-sm transition-colors ${
                        interviewers.includes(s.id)
                          ? 'border-primary bg-primary/10 text-primary'
                          : 'border-line bg-white/70 text-foreground/70 hover:bg-white'
                      }`}
                    >
                      <input
                        type="checkbox"
                        className="sr-only"
                        checked={interviewers.includes(s.id)}
                        onChange={() =>
                          setInterviewers((cur) =>
                            cur.includes(s.id) ? cur.filter((i) => i !== s.id) : [...cur, s.id],
                          )
                        }
                      />
                      {s.name}
                    </label>
                  ))}
                </div>
              </div>
              <div>
                <Label>Location</Label>
                <Input placeholder="Office address" {...register('location')} />
              </div>
              <div>
                <Label>Meeting URL</Label>
                <Input type="url" placeholder="https://meet.google.com/…" {...register('meeting_url')} />
              </div>
              <div>
                <Label>Notes</Label>
                <textarea
                  {...register('notes')}
                  rows={3}
                  className="w-full rounded-lg border border-line bg-white/70 px-3 py-2 text-sm transition-colors focus:border-primary focus:outline-none"
                />
              </div>
              {error && <p className="text-sm text-destructive">{error}</p>}
              <Button type="submit">Schedule</Button>
            </form>
          </Card>

          <div className="space-y-3">
            {interviews.isLoading && <p className="text-foreground/60">Loading…</p>}
            {interviews.data?.map((it: Interview) => (
              <Card key={it.id}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p className="font-medium">
                      {it.candidate?.name} — {it.job?.title}
                    </p>
                    <p className="text-sm text-foreground/70">
                      {it.type} · {new Date(it.scheduled_at).toLocaleString()}
                      {it.location ? ` · ${it.location}` : ''}
                    </p>
                    {it.meeting_url && (
                      <a
                        href={it.meeting_url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-sm font-medium text-primary hover:underline"
                      >
                        Join meeting
                      </a>
                    )}
                    {it.notes && <p className="mt-1 text-sm text-foreground/70">{it.notes}</p>}
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <StatusBadge status={it.status} />
                    {(it.status === 'scheduled' || it.status === 'rescheduled') && (
                      <>
                        <Button variant="ghost" onClick={() => move(it.id, 'completed')}>
                          Complete
                        </Button>
                        <Button variant="ghost" onClick={() => move(it.id, 'cancelled')}>
                          Cancel
                        </Button>
                        <Button variant="ghost" onClick={() => move(it.id, 'no_show')}>
                          No-show
                        </Button>
                        <Button variant="ghost" onClick={() => setRescheduling(it.id)}>
                          Reschedule
                        </Button>
                      </>
                    )}
                  </div>
                </div>
                {rescheduling === it.id && (
                  <div className="mt-3 flex items-center gap-2">
                    <Input type="datetime-local" value={rescheduleTime} onChange={(e) => setRescheduleTime(e.target.value)} />
                    <Button onClick={() => doReschedule(it.id)}>Save</Button>
                    <Button variant="ghost" onClick={() => setRescheduling(null)}>
                      Cancel
                    </Button>
                  </div>
                )}
              </Card>
            ))}
            {interviews.data?.length === 0 && (
              <Card>
                <p className="text-foreground/60">No interviews scheduled.</p>
              </Card>
            )}
          </div>
        </div>
      </div>
    </AppLayout>
  )
}