import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { applicationHistory } from '../services/api'

export default function ApplicationTimeline({ applicationId }: { applicationId: number }) {
  const [open, setOpen] = useState(false)
  const { data, isLoading } = useQuery({
    queryKey: ['application-history', applicationId],
    queryFn: () => applicationHistory(applicationId).then((r) => r.data.data),
    enabled: open,
  })

  return (
    <div className="mt-3">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="text-sm text-primary hover:underline"
      >
        {open ? 'Hide history' : 'Show history'}
      </button>
      {open ? (
        <ol className="mt-3 space-y-3 border-l-2 border-slate-200 pl-4">
          {isLoading ? (
            <li className="text-sm text-foreground/60">Loading…</li>
          ) : (
            data?.map((h) => (
              <li key={h.id} className="relative">
                <span className="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-primary bg-white" />
                <p className="text-sm capitalize text-foreground">
                  {h.from_status ? `${h.from_status.replace('_', ' ')} → ${h.to_status.replace('_', ' ')}` : `Applied (${h.to_status})`}
                </p>
                {h.comment ? <p className="mt-0.5 text-xs italic text-foreground/60">“{h.comment}”</p> : null}
                <p className="mt-0.5 text-xs text-foreground/40">
                  {new Date(h.created_at).toLocaleString()}
                  {h.changed_by ? ` · by ${h.changed_by}` : ''}
                </p>
              </li>
            ))
          )}
        </ol>
      ) : null}
    </div>
  )
}