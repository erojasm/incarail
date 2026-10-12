import { ref } from 'vue'
import { defineStore } from 'pinia'

type Color = 'success' | 'error' | 'warning' | 'info'

export const useNotifyStore = defineStore('notify', () => {
  const visible = ref(false)
  const text = ref('')
  const color = ref<Color>('info')

  function show(message: string, type: Color = 'info') {
    text.value = message
    color.value = type
    visible.value = true
  }

  return {
    visible, text, color, show,
    success: (m: string) => show(m, 'success'),
    error: (m: string) => show(m, 'error'),
  }
})
