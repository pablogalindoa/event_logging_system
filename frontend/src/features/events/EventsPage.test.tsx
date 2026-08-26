import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { fireEvent, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { ReactNode } from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { Event, EventsResponse } from '../../api/types'
import { EventsPage } from './EventsPage'

const event: Event = {
  id: 42,
  level: 'error',
  message: 'Payment authorization failed',
  source: 'checkout-api',
  context: { order_id: 'ord_123' },
  occurred_at: '2026-08-26T00:42:13.381Z',
  created_at: '2026-08-26T00:42:14.027Z',
}

function response(events: Event[], overrides: Partial<EventsResponse['meta']> = {}): EventsResponse {
  const total = overrides.total ?? events.length
  const currentPage = overrides.current_page ?? 1
  const lastPage = overrides.last_page ?? 1

  return {
    data: events,
    meta: {
      current_page: currentPage,
      per_page: 25,
      last_page: lastPage,
      total,
      from: total ? (currentPage - 1) * 25 + 1 : null,
      to: total ? Math.min(currentPage * 25, total) : null,
      ...overrides,
    },
    links: {
      first: 'http://localhost:8000/events?page=1',
      last: `http://localhost:8000/events?page=${lastPage}`,
      previous: currentPage > 1 ? `http://localhost:8000/events?page=${currentPage - 1}` : null,
      next: currentPage < lastPage ? `http://localhost:8000/events?page=${currentPage + 1}` : null,
    },
  }
}

function jsonResponse(body: unknown, status = 200) {
  return Promise.resolve(new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  }))
}

function renderPage() {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false, gcTime: 0 } },
  })

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={client}>{children}</QueryClientProvider>
  }

  return render(<EventsPage />, { wrapper: Wrapper })
}

describe('EventsPage', () => {
  beforeEach(() => {
    window.history.replaceState(null, '', '/')
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('shows a loading state while the request is pending', () => {
    vi.stubGlobal('fetch', vi.fn(() => new Promise<Response>(() => undefined)))
    renderPage()
    expect(screen.getByRole('status')).toHaveTextContent('Loading events')
  })

  it('renders events returned by the API', async () => {
    vi.stubGlobal('fetch', vi.fn(() => jsonResponse(response([event]))))
    renderPage()
    expect(await screen.findByText('Payment authorization failed')).toBeInTheDocument()
    expect(screen.getByText('checkout-api')).toBeInTheDocument()
    expect(screen.getByText('error')).toBeInTheDocument()
  })

  it('shows an empty state', async () => {
    vi.stubGlobal('fetch', vi.fn(() => jsonResponse(response([]))))
    renderPage()
    expect(await screen.findByText('No events found')).toBeInTheDocument()
  })

  it('shows an API error state', async () => {
    vi.stubGlobal('fetch', vi.fn(() => jsonResponse({
      error: {
        code: 'INTERNAL_ERROR',
        message: 'An unexpected error occurred.',
        details: {},
        request_id: 'request-123',
      },
    }, 500)))
    renderPage()
    expect(await screen.findByRole('alert')).toHaveTextContent('Unable to load events')
    expect(screen.getByRole('alert')).toHaveTextContent('request-123')
  })

  it('updates the URL and request when the level changes', async () => {
    const fetchMock = vi.fn((_input: string | URL | Request) => jsonResponse(response([])))
    vi.stubGlobal('fetch', fetchMock)
    const user = userEvent.setup()
    renderPage()
    await screen.findByText('No events found')

    await user.selectOptions(screen.getByLabelText('Level'), 'critical')

    await waitFor(() => expect(window.location.search).toContain('level=critical'))
    await waitFor(() => expect(fetchMock.mock.calls.at(-1)?.[0]).toContain('level=critical'))
  })

  it('moves to the next server-side page', async () => {
    const fetchMock = vi.fn((input: string | URL | Request) => {
      const page = Number(new URL(String(input)).searchParams.get('page'))
      return jsonResponse(response([event], { current_page: page, last_page: 2, total: 26 }))
    })
    vi.stubGlobal('fetch', fetchMock)
    renderPage()
    await screen.findByText('Payment authorization failed')

    fireEvent.click(screen.getByRole('button', { name: 'Next' }))

    await waitFor(() => expect(window.location.search).toContain('page=2'))
    await waitFor(() => expect(fetchMock.mock.calls.at(-1)?.[0]).toContain('page=2'))
    expect(await screen.findByText('Page 2 of 2')).toBeInTheDocument()
  })
})
