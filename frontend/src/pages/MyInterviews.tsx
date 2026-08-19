import { useQuery } from '@tanstack/react-query'
import { myInterviews } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Card, StatusBadge } from '../components/ui'

export default function MyInterviews() {
  const { data, isLoading } = useQuery({
    queryKey: ['my-interviews'],
    queryFn: () => myInterviews().then((r) => r.data.data),
  })

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">My Interviews</h1>

        <div className="mt-8 space-y-3">
          {isLoading && <p className="text-foreground/60">Loading…</p>}
          {data?.map((it) => (
            <Card key={it.id} className="flex items-center justify-between gap-4">
              <div>
                <p className="font-medium">{it.job?.title}</p>
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
              </div>
              <StatusBadge status={it.status} />
            </Card>
          ))}
          {data?.length === 0 && (
            <Card>
              <p className="text-foreground/60">No interviews scheduled yet.</p>
            </Card>
          )}
        </div>
      </div>
    </AppLayout>
  )
}