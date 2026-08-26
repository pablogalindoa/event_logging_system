import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { ApiError } from '../../api/client'
import { EventFiltersBar } from './EventFiltersBar'
import { EventList } from './EventList'
import { Pagination } from './Pagination'
import { fetchEvents } from './api'
import { useEventFilters } from './useEventFilters'

export function EventsPage() {
  const { filters, updateFilters } = useEventFilters()
  const query = useQuery({
    queryKey: ['events', filters],
    queryFn: ({ signal }) => fetchEvents(filters, signal),
    placeholderData: keepPreviousData,
    refetchInterval: 5_000,
  })

  return (
    <main className="mx-auto min-h-screen w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <header className="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="mb-1 text-sm font-semibold uppercase tracking-wider text-blue-700">Monitoring</p>
          <h1 className="text-3xl font-bold tracking-tight text-slate-950">Events</h1>
          <p className="mt-1 text-sm text-slate-600">Recent application events, refreshed every five seconds.</p>
        </div>
        {query.isFetching && !query.isPending && <span className="text-sm text-slate-500" role="status">Refreshing…</span>}
      </header>

      <div className="grid gap-4">
        <EventFiltersBar filters={filters} onChange={updateFilters} />

        {query.isPending && <div className="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-600" role="status">Loading events…</div>}

        {query.isError && (
          <div className="rounded-xl border border-red-200 bg-red-50 p-6" role="alert">
            <h2 className="font-semibold text-red-900">Unable to load events</h2>
            <p className="mt-1 text-sm text-red-800">{query.error.message}</p>
            {query.error instanceof ApiError && query.error.requestId && <p className="mt-2 text-xs text-red-700">Request ID: {query.error.requestId}</p>}
            <button type="button" className="mt-4 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800" onClick={() => void query.refetch()}>Try again</button>
          </div>
        )}

        {query.isSuccess && query.data.data.length === 0 && (
          <div className="rounded-xl border border-slate-200 bg-white p-10 text-center">
            <h2 className="font-semibold text-slate-900">No events found</h2>
            <p className="mt-1 text-sm text-slate-600">Adjust the filters or wait for new events to arrive.</p>
          </div>
        )}

        {query.isSuccess && query.data.data.length > 0 && <EventList events={query.data.data} />}

        {query.isSuccess && (
          <Pagination
            meta={query.data.meta}
            links={query.data.links}
            onPageChange={(page) => updateFilters({ page })}
            onPerPageChange={(perPage) => updateFilters({ per_page: perPage, page: undefined })}
          />
        )}
      </div>
    </main>
  )
}
