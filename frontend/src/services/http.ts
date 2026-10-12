import axios from 'axios'
import type { ApiError } from '@/types'

const TOKEN_KEY = 'incarail_token'

export const tokenStorage = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (token: string) => localStorage.setItem(TOKEN_KEY, token),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
  timeout: 30000,
})

let unauthorizedHandler: () => void = () => {}

export function onUnauthorized(handler: () => void) {
  unauthorizedHandler = handler
}

export function isApiError(value: unknown): value is ApiError {
  return typeof value === 'object' && value !== null && 'status' in value && 'message' in value && 'code' in value
}

// Normaliza cualquier error al formato del API
export function toApiError(error: unknown): ApiError {
  if (isApiError(error)) return error

  if (axios.isAxiosError(error)) {
    if (!error.response) {
      return { status: 0, code: 'NETWORK_ERROR', message: 'No se pudo conectar con el servidor. Revisa tu conexión.' }
    }
    const data = (error.response.data ?? {}) as Partial<ApiError>
    return {
      status: error.response.status,
      code: data.code ?? 'HTTP_ERROR',
      message: data.message ?? 'Ocurrió un error inesperado.',
      errors: data.errors,
      retry_after: data.retry_after,
    }
  }

  return { status: 0, code: 'UNKNOWN', message: 'Ocurrió un error inesperado.' }
}

http.interceptors.request.use((config) => {
  const token = tokenStorage.get()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

http.interceptors.response.use(
  (response) => response,
  (error) => {
    const apiError = toApiError(error)
    // Sesión vencida: limpiamos y mandamos al login
    if (apiError.status === 401 && !error.config?.url?.endsWith('/login')) {
      unauthorizedHandler()
    }
    return Promise.reject(apiError)
  },
)
