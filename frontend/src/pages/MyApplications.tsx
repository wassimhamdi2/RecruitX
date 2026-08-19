import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { myApplications } from '../services/api'
import AppLayout from '../components/AppLayout'
import ApplicationTimeline from '../components/ApplicationTimeline'
import { Card, StatusBadge } from '../components/ui'

export default function MyApplications() {
  const { data, isLoading } = useQuery({
    queryKey: ['applications'],
    queryFn: () => myApplications().then((r) => r.data.data),
  })

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">My Applications</h1>

        <div className="mt-8 space-y-3">
          {isLoading && <p className="text-foreground/60">Loading…</p>}
          {data?.map((app) => (
            <Card key={app.id} className="flex items-start justify-between gap-4">
              <div className="flex-1">
                <Link to={`/jobs/${app.job.slug}`} className="font-medium hover:text-primary">
                  {app.job.title}
                </Link>
                <p className="text-sm text-foreground/70">{app.job.company}</p>
                <ApplicationTimeline applicationId={app.id} />
              </div>
              <StatusBadge status={app.status} />
            </Card>
          ))}
          {data?.length === 0 && (
            <Card>
              <p className="text-foreground/60">You have not applied to any jobs yet.</p>
            </Card>
          )}
        </div>
      </div>
    </AppLayout>
  )
}