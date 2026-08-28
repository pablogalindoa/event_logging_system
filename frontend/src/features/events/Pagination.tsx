import type { PaginationLinks, PaginationMeta } from '../../api/types'

interface Props {
  meta: PaginationMeta
  links: PaginationLinks
  onPageChange: (page: number) => void
  onPerPageChange: (perPage: number) => void
}

export function Pagination({ meta, links, onPageChange, onPerPageChange }: Props) {
  return (
    <nav aria-label="Event pagination" className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm sm:flex-row sm:items-center sm:justify-between">
      <p className="text-slate-600" aria-live="polite">
        {meta.total === 0 ? 'No events' : `Showing ${meta.from}–${meta.to} of ${meta.total}`}
      </p>
      <div className="flex min-w-0 max-w-full flex-wrap items-center gap-2">
        <label className="mr-2 flex items-center gap-2 text-slate-600">
          Per page
          <select
            className="h-9 rounded-lg border border-slate-300 bg-white px-2 text-slate-900"
            value={meta.per_page}
            onChange={(event) => onPerPageChange(Number(event.target.value))}
          >
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </label>
        <button type="button" className="h-9 rounded-lg border border-slate-300 px-3 font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" disabled={!links.previous} onClick={() => onPageChange(meta.current_page - 1)}>
          Previous
        </button>
        <span className="min-w-20 text-center text-slate-600">Page {meta.current_page} of {meta.last_page}</span>
        <button type="button" className="h-9 rounded-lg border border-slate-300 px-3 font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" disabled={!links.next} onClick={() => onPageChange(meta.current_page + 1)}>
          Next
        </button>
      </div>
    </nav>
  )
}
