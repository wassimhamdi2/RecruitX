import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { changeApplicationStatus, downloadApplicationCv, recruiterApplications } from '../services/api'
import type { Application } from '../services/api'
import AppLayout from '../components/AppLayout'
import ApplicationTimeline from '../components/ApplicationTimeline'
import { Button, Card, Select, StatusBadge, StatusSelect } from '../components/ui'

const TERMINAL = ['hired', 'rejected', 'withdrawn']

export default function RecruiterApplications() {
  const [statusFilter, setStatusFilter] = useState('')
  const queryClient = useQueryClient()
  const { data, isLoading } = useQuery({
    queryKey: ['recruiter-applications', statusFilter],
    queryFn: () =>
      recruiterApplications(statusFilter ? { status: statusFilter } : {}).then((r) => r.data.data),
  })

  const changeStatus = async (app: Application, status: string) => {
    await changeApplicationStatus(app.id, status)
    queryClient.invalidateQueries({ queryKey: ['recruiter-applications'] })
  }

  return (
    <AppLayout>
      <div className="py-10">
        <div className="flex items-center justify-between gap-4">
          <div>
            <h1 className="font-display text-2xl font-semibold">Applications</h1>
            <p className="mt-1 text-sm text-foreground/70">Move candidates through the pipeline</p>
          </div>
          <Select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
            <option value="">All statuses</option>
            <option value="applied">Applied</option>
            <option value="screening">Screening</option>
            <option value="shortlisted">Shortlisted</option>
            <option value="interview">Interview</option>
            <option value="evaluation">Evaluation</option>
            <option value="offer">Offer</option>
            <option value="hired">Hired</option>
            <option value="rejected">Rejected</option>
            <option value="withdrawn">Withdrawn</option>
          </Select>
        </div>

        <div className="mt-8 space-y-3">
          {isLoading && <p className="text-foreground/60">Loading…</p>}
          {data?.map((app) => (
            <Card key={app.id} className="flex flex-wrap items-start justify-between gap-4">
              <div className="flex-1">
                <p className="font-medium">{app.candidate?.name}</p>
                <p className="text-sm text-foreground/70">
                  {app.job.title} · {app.job.company}
                </p>
                <ApplicationTimeline applicationId={app.id} />
              </div>
              <div className="flex items-center gap-3">
                <StatusBadge status={app.status} />
                {TERMINAL.includes(app.status) ? null : (
                  <StatusSelect exclude={[app.status]} onMove={(s) => changeStatus(app, s)} />
                )}
                {app.candidate?.has_cv && (
                  <Button variant="ghost" onClick={() => downloadApplicationCv(app.id)}>
                    View CV
                  </Button>
                )}
              </div>
            </Card>
          ))}
          {data?.length === 0 && (
            <Card>
              <p className="text-foreground/60">No applications.</p>
            </Card>
          )}
        </div>
      </div>
    </AppLayout>
  )
}