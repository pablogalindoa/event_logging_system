import { useSyncExternalStore } from 'react'
import type { EventLevel } from '../../api/types'
import type { EventFilters } from './api'

const levels: EventLevel[] = ['debug', 'info', 'warning', 'error', 'critical']

function subscribe(callback: () => void) {
  window.addEventListener('popstate', callback)
  return () => window.removeEventListener('popstate', callback)
}

function getSnapshot() {
  return window.location.search
}

function positiveInteger(value: string | null, fallback: number) {
  const parsed = Number(value)
  return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback
}

export function useEventFilters() {
  const search = useSyncExternalStore(subscribe, getSnapshot)
  const params = new URLSearchParams(search)
  const rawLevel = params.get('level')
  const rawPerPage = positiveInteger(params.get('per_page'), 25)

  const filters: EventFilters = {
    page: positiveInteger(params.get('page'), 1),
    perPage: [25, 50, 100].includes(rawPerPage) ? rawPerPage : 25,
    level: levels.includes(rawLevel as EventLevel) ? (rawLevel as EventLevel) : undefined,
    from: params.get('from') || undefined,
    to: params.get('to') || undefined,
  }

  function updateFilters(updates: Record<string, string | number | undefined>) {
    const next = new URLSearchParams(window.location.search)

    Object.entries(updates).forEach(([key, value]) => {
      if (value === undefined || value === '') next.delete(key)
      else next.set(key, String(value))
    })

    const nextSearch = next.toString()
    window.history.pushState(null, '', `${window.location.pathname}${nextSearch ? `?${nextSearch}` : ''}`)
    window.dispatchEvent(new PopStateEvent('popstate'))
  }

  return { filters, updateFilters }
}
