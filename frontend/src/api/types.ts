export type EventLevel = 'debug' | 'info' | 'warning' | 'error' | 'critical'

export interface Event {
  id: number
  level: EventLevel
  message: string
  source: string | null
  context: Record<string, unknown> | null
  occurred_at: string
  created_at: string
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  last_page: number
  total: number
  from: number | null
  to: number | null
}

export interface PaginationLinks {
  first: string
  last: string
  previous: string | null
  next: string | null
}

export interface EventsResponse {
  data: Event[]
  meta: PaginationMeta
  links: PaginationLinks
}

export interface ApiErrorBody {
  error: {
    code: string
    message: string
    details: Record<string, string[]>
    request_id: string | null
  }
}
