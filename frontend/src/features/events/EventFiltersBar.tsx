import type { EventLevel } from '../../api/types'
import type { EventFilters } from './api'

interface Props {
  filters: EventFilters
  onChange: (updates: Record<string, string | number | undefined>) => void
}

const levels: EventLevel[] = ['debug', 'info', 'warning', 'error', 'critical']

function toLocalInput(iso?: string) {
  if (!iso) return ''
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return ''
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000)
  return local.toISOString().slice(0, 19)
}

function toIso(local: string) {
  return local ? new Date(local).toISOString() : undefined
}

export function EventFiltersBar({ filters, onChange }: Props) {
  return (
    <section aria-label="Event filters" className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1.4fr_1.4fr_auto] lg:items-end">
        <label className="grid gap-1.5 text-sm font-medium text-slate-700">
          Level
          <select
            className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-slate-900"
            value={filters.level ?? ''}
            onChange={(event) => onChange({ level: event.target.value || undefined, page: undefined })}
          >
            <option value="">All levels</option>
            {levels.map((level) => (
              <option key={level} value={level}>{level[0].toUpperCase() + level.slice(1)}</option>
            ))}
          </select>
        </label>

        <label className="grid gap-1.5 text-sm font-medium text-slate-700">
          From
          <input
            className="h-10 rounded-lg border border-slate-300 px-3 text-slate-900"
            type="datetime-local"
            step="1"
            value={toLocalInput(filters.from)}
            onChange={(event) => onChange({ from: toIso(event.target.value), page: undefined })}
          />
        </label>

        <label className="grid gap-1.5 text-sm font-medium text-slate-700">
          To
          <input
            className="h-10 rounded-lg border border-slate-300 px-3 text-slate-900"
            type="datetime-local"
            step="1"
            value={toLocalInput(filters.to)}
            onChange={(event) => onChange({ to: toIso(event.target.value), page: undefined })}
          />
        </label>

        <button
          type="button"
          className="h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          disabled={!filters.level && !filters.from && !filters.to}
          onClick={() => onChange({ level: undefined, from: undefined, to: undefined, page: undefined })}
        >
          Clear filters
        </button>
      </div>
    </section>
  )
}
