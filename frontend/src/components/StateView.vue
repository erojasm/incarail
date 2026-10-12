<script setup lang="ts">
import { computed } from 'vue'
import type { ApiError } from '@/types'

// Muestra carga, error o vacío; si no hay nada de eso, el contenido
const props = defineProps<{
  loading?: boolean
  error?: ApiError | null
  empty?: boolean
  emptyText?: string
  loadingText?: string
}>()

defineEmits<{ retry: [] }>()

const errorIcon = computed(() => {
  switch (props.error?.status) {
    case 429: return 'mdi-timer-sand'
    case 403: return 'mdi-lock-outline'
    case 404: return 'mdi-magnify-close'
    case 0: return 'mdi-wifi-off'
    default: return 'mdi-alert-circle-outline'
  }
})
</script>

<template>
  <div v-if="loading" class="text-center py-12">
    <v-progress-circular indeterminate color="primary" size="48" />
    <p class="mt-4 text-medium-emphasis">{{ loadingText ?? 'Cargando...' }}</p>
  </div>

  <v-card v-else-if="error" variant="tonal" color="error" class="text-center py-8 px-4">
    <v-icon :icon="errorIcon" size="48" />
    <p class="text-title-medium mt-3">{{ error.message }}</p>
    <v-btn v-if="error.status !== 403" class="mt-4" variant="flat" color="error" prepend-icon="mdi-refresh" @click="$emit('retry')">
      Reintentar
    </v-btn>
  </v-card>

  <div v-else-if="empty" class="text-center py-12 text-medium-emphasis">
    <v-icon icon="mdi-inbox-outline" size="48" />
    <p class="mt-3">{{ emptyText ?? 'No hay datos para mostrar.' }}</p>
    <slot name="empty-action" />
  </div>

  <slot v-else />
</template>
