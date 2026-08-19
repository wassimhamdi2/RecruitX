import { useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import {
  RECOMMENDATIONS,
  createEvaluation,
  evaluationCriteria,
  recruiterEvaluations,
  recruiterInterviews,
} from '../services/api'
import type { Evaluation, EvaluationCriterion } from '../services/api'
import AppLayout from '../components/AppLayout'
import { Button, Card, Input, Label, Select } from '../components/ui'

export default function RecruiterEvaluations() {
  const [interviewId, setInterviewId] = useState('')
  const [recommendation, setRecommendation] = useState('')
  const [comments, setComments] = useState('')
  const [scores, setScores] = useState<Record<string, string>>({})
  const [error, setError] = useState('')
  const queryClient = useQueryClient()

  const evaluations = useQuery({
    queryKey: ['recruiter-evaluations'],
    queryFn: () => recruiterEvaluations().then((r) => r.data.data),
  })
  const interviews = useQuery({
    queryKey: ['recruiter-interviews'],
    queryFn: () => recruiterInterviews().then((r) => r.data.data),
  })
  const criteria = useQuery({
    queryKey: ['evaluation-criteria'],
    queryFn: () => evaluationCriteria().then((r) => r.data.data),
  })

  const evaluatedIds = new Set(evaluations.data?.map((e) => e.interview?.id) ?? [])
  const pending = interviews.data?.filter((it) => !evaluatedIds.has(it.id)) ?? []

  const submit = async () => {
    setError('')
    if (!interviewId || !recommendation) {
      setError('Select an interview and a recommendation.')
      return
    }
    const payloadScores = Object.entries(scores)
      .filter(([, v]) => v !== '')
      .map(([criterionId, score]) => ({ criterion_id: Number(criterionId), score: Number(score) }))
    if (payloadScores.length === 0) {
      setError('Add at least one score.')
      return
    }
    const interview = interviews.data?.find((it) => it.id === Number(interviewId))
    const applicationId = interview?.application_id
    if (!applicationId) {
      setError('Could not resolve application.')
      return
    }
    try {
      await createEvaluation(applicationId, Number(interviewId), {
        recommendation,
        comments: comments || undefined,
        scores: payloadScores,
      })
      setInterviewId('')
      setRecommendation('')
      setComments('')
      setScores({})
      queryClient.invalidateQueries({ queryKey: ['recruiter-evaluations'] })
    } catch {
      setError('Could not submit evaluation.')
    }
  }

  const recBadge: Record<string, string> = {
    strong_yes: 'bg-green-100 text-green-800',
    yes: 'bg-green-100 text-green-800',
    maybe: 'bg-amber-100 text-amber-800',
    no: 'bg-red-100 text-red-800',
    strong_no: 'bg-red-100 text-red-800',
  }

  return (
    <AppLayout>
      <div className="py-10">
        <h1 className="font-display text-2xl font-semibold">Evaluations</h1>

        <div className="mt-8 grid gap-6 lg:grid-cols-2">
          <Card>
            <h2 className="font-display text-lg font-semibold">Evaluate interview</h2>
            <div className="mt-4 space-y-4">
              <div>
                <Label>Interview</Label>
                <Select className="w-full" value={interviewId} onChange={(e) => setInterviewId(e.target.value)}>
                  <option value="" disabled>
                    Select interview…
                  </option>
                  {pending.map((it) => (
                    <option key={it.id} value={it.id}>
                      {it.candidate?.name} — {it.job?.title} ({new Date(it.scheduled_at).toLocaleDateString()})
                    </option>
                  ))}
                </Select>
              </div>
              <div>
                <Label>Recommendation</Label>
                <Select className="w-full" value={recommendation} onChange={(e) => setRecommendation(e.target.value)}>
                  <option value="" disabled>
                    Recommendation…
                  </option>
                  {RECOMMENDATIONS.map((r) => (
                    <option key={r} value={r}>
                      {r.replace('_', ' ')}
                    </option>
                  ))}
                </Select>
              </div>
              {criteria.data?.map((c: EvaluationCriterion) => (
                <div key={c.id}>
                  <Label>
                    {c.name} (0–{c.max_score})
                  </Label>
                  <Input
                    type="number"
                    min={0}
                    max={c.max_score}
                    value={scores[String(c.id)] ?? ''}
                    onChange={(e) => setScores((s) => ({ ...s, [String(c.id)]: e.target.value }))}
                  />
                </div>
              ))}
              <div>
                <Label>Comments</Label>
                <textarea
                  value={comments}
                  onChange={(e) => setComments(e.target.value)}
                  rows={3}
                  className="w-full rounded-lg border border-line bg-white/70 px-3 py-2 text-sm transition-colors focus:border-primary focus:outline-none"
                />
              </div>
              {error && <p className="text-sm text-destructive">{error}</p>}
              <Button onClick={submit}>Submit evaluation</Button>
            </div>
          </Card>

          <div className="space-y-3">
            {evaluations.isLoading && <p className="text-foreground/60">Loading…</p>}
            {evaluations.data?.map((e: Evaluation) => (
              <Card key={e.id}>
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <p className="font-medium">
                      {e.candidate?.name} — {e.job?.title}
                    </p>
                    <p className="text-sm text-foreground/70">
                      {e.interview?.type} · {new Date(e.interview?.scheduled_at ?? '').toLocaleString()} · by {e.evaluator}
                    </p>
                    {e.scores?.map((s) => (
                      <p key={s.criterion} className="text-sm text-foreground/70">
                        {s.criterion}: {s.score}/{s.max_score}
                      </p>
                    ))}
                    {e.comments && <p className="mt-1 text-sm text-foreground/70">{e.comments}</p>}
                  </div>
                  <div className="flex flex-col items-end gap-2">
                    <span
                      className={`rounded-full px-3 py-1 text-xs font-medium capitalize ${recBadge[e.recommendation ?? ''] ?? 'bg-slate-100 text-slate-700'}`}
                    >
                      {e.recommendation?.replace('_', ' ')}
                    </span>
                    {e.overall_score !== null && (
                      <span className="text-sm font-semibold text-primary">{e.overall_score}/10</span>
                    )}
                  </div>
                </div>
              </Card>
            ))}
            {evaluations.data?.length === 0 && (
              <Card>
                <p className="text-foreground/60">No evaluations yet.</p>
              </Card>
            )}
          </div>
        </div>
      </div>
    </AppLayout>
  )
}