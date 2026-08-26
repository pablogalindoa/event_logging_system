import type { ApiErrorBody } from './types'

const configuredBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
export const API_BASE_URL = configuredBaseUrl.replace(/\/+$/, '')

export class ApiError extends Error {
  readonly status: number
  readonly code: string
  readonly requestId: string | null

  constructor(
    message: string,
    status: number,
    code: string,
    requestId: string | null,
  ) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.requestId = requestId
  }
}

export async function getJson<T>(path: string, signal?: AbortSignal): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: { Accept: 'application/json' },
    signal,
  })

  if (!response.ok) {
    let body: ApiErrorBody | null = null

    try {
      body = (await response.json()) as ApiErrorBody
    } catch {
      // The fallback also handles proxy and network-layer error pages.
    }

    throw new ApiError(
      body?.error.message ?? 'The event service is currently unavailable.',
      response.status,
      body?.error.code ?? 'REQUEST_FAILED',
      body?.error.request_id ?? response.headers.get('X-Request-ID'),
    )
  }

  return (await response.json()) as T
}
