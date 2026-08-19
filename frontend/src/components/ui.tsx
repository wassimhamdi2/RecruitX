import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, SelectHTMLAttributes } from 'react'
import { APPLICATION_STATUSES } from '../services/api'

export function Button({
  variant = 'primary',
  className = '',
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: 'primary' | 'ghost' | 'danger' }) {
  const styles = {
    primary: 'bg-gradient-to-b from-primary to-primary-dark text-white shadow-sm shadow-primary/20 hover:shadow-md hover:shadow-primary/25 hover:brightness-110',
    ghost: 'bg-white text-foreground border border-line shadow-sm hover:border-primary/40 hover:bg-muted/60',
    danger: 'bg-destructive text-white shadow-sm shadow-destructive/20 hover:brightness-110',
  }[variant]

  return (
    <button
      type="button"
      className={`inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-150 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100 ${styles} ${className}`}
      {...props}
    />
  )
}

export function Card({ className = '', children }: { className?: string; children: ReactNode }) {
  return (
    <div
      className={`rounded-xl border border-white/70 bg-white/70 p-5 shadow-md shadow-sky-900/5 backdrop-blur-md transition-shadow duration-200 hover:shadow-lg hover:shadow-sky-900/10 ${className}`}
    >
      {children}
    </div>
  )
}

export function Label({ children }: { children: ReactNode }) {
  return <label className="mb-1 block text-sm font-medium text-foreground/80">{children}</label>
}

export function Input({ className = '', ...props }: InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      className={`w-full rounded-lg border border-line bg-white/80 px-3 py-2 text-sm shadow-sm transition-all duration-150 hover:border-primary/50 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none ${className}`}
      {...props}
    />
  )
}

export function Select({ className = '', children, ...props }: SelectHTMLAttributes<HTMLSelectElement>) {
  return (
    <select
      className={`rounded-lg border border-line bg-white px-3 py-2 text-sm shadow-sm transition-all duration-150 hover:border-primary/50 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none ${className}`}
      {...props}
    >
      {children}
    </select>
  )
}

const STATUS_STYLES: Record<string, string> = {
  applied: 'bg-sky-100 text-sky-800',
  screening: 'bg-amber-100 text-amber-800',
  shortlisted: 'bg-blue-100 text-blue-800',
  interview: 'bg-purple-100 text-purple-800',
  evaluation: 'bg-indigo-100 text-indigo-800',
  offer: 'bg-orange-100 text-orange-800',
  hired: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
  withdrawn: 'bg-slate-200 text-slate-700',
}

export function StatusBadge({ status }: { status: string }) {
  return (
    <span
      className={`rounded-full px-3 py-1 text-xs font-medium capitalize ${STATUS_STYLES[status] ?? 'bg-slate-100 text-slate-700'}`}
    >
      {status}
    </span>
  )
}

export function StatusSelect({
  onMove,
  exclude,
}: {
  onMove: (status: string) => void
  exclude: string[]
}) {
  return (
    <Select value="" onChange={(e) => e.target.value && onMove(e.target.value)}>
      <option value="" disabled>
        Move to…
      </option>
      {APPLICATION_STATUSES.filter((s) => !exclude.includes(s)).map((s) => (
        <option key={s} value={s}>
          {s}
        </option>
      ))}
    </Select>
  )
}