import { getJson } from '../../api/client'
import type { EventLevel, EventsResponse } from '../../api/types'

export interface EventFilters {
  page: number
  perPage: number
  level?: EventLevel
  from?: string
  to?: string
}

export function fetchEvents(filters: EventFilters, signal?: AbortSignal): Promise<EventsResponse> {
  const query = new URLSearchParams({
    page: String(filters.page),
    per_page: String(filters.perPage),
  })

  if (filters.level) query.set('level', filters.level)
  if (filters.from) query.set('from', filters.from)
  if (filters.to) query.set('to', filters.to)

  return getJson<EventsResponse>(`/events?${query.toString()}`, signal)
}
