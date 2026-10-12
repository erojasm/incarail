import { ref, watch, type Ref } from 'vue'

export function useDebounced<T>(source: Ref<T>, delay = 400) {
  const debounced = ref(source.value) as Ref<T>
  let timer: ReturnType<typeof setTimeout> | undefined

  watch(source, (value) => {
    clearTimeout(timer)
    timer = setTimeout(() => (debounced.value = value), delay)
  })

  return debounced
}
