import type { Event, EventLevel } from '../../api/types'

const levelStyles: Record<EventLevel, string> = {
  debug: 'bg-slate-100 text-slate-700',
  info: 'bg-blue-50 text-blue-700',
  warning: 'bg-amber-50 text-amber-800',
  error: 'bg-red-50 text-red-700',
  critical: 'bg-purple-50 text-purple-800',
}

function displayTimestamp(timestamp: string) {
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'medium',
  }).format(new Date(timestamp))
}

export function EventList({ events }: { events: Event[] }) {
  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="hidden grid-cols-[8rem_minmax(0,1fr)_12rem_13rem] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 md:grid">
        <span>Level</span><span>Message</span><span>Source</span><span>Occurred</span>
      </div>
      <ul className="divide-y divide-slate-200" aria-label="Events">
        {events.map((event) => (
          <li key={event.id} className="grid gap-3 px-4 py-4 md:grid-cols-[8rem_minmax(0,1fr)_12rem_13rem] md:items-center md:gap-4 md:px-5">
            <div>
              <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${levelStyles[event.level]}`}>
                {event.level}
              </span>
            </div>
            <p className="min-w-0 break-words text-sm font-medium text-slate-900">{event.message}</p>
            <p className="min-w-0 truncate text-sm text-slate-600">
              <span className="mr-2 font-medium text-slate-500 md:hidden">Source</span>
              {event.source ?? '—'}
            </p>
            <time className="text-sm text-slate-600" dateTime={event.occurred_at}>
              <span className="mr-2 font-medium text-slate-500 md:hidden">Occurred</span>
              {displayTimestamp(event.occurred_at)}
            </time>
          </li>
        ))}
      </ul>
    </div>
  )
}
