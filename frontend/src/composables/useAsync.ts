import { ref, shallowRef, type Ref } from 'vue'
import { toApiError } from '@/services/http'
import type { ApiError } from '@/types'

// Estado de carga / error / datos para una llamada al API
export function useAsync<T, A extends unknown[] = []>(fn: (...args: A) => Promise<T>) {
  const data = shallowRef<T | null>(null) as Ref<T | null>
  const loading = ref(false)
  const error = ref<ApiError | null>(null)

  async function run(...args: A): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      data.value = await fn(...args)
      return data.value
    } catch (e) {
      error.value = toApiError(e)
      return null
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, run }
}
