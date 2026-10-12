<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { teamsApi, competitionsApi } from '@/services/api'
import { toApiError } from '@/services/http'
import { useDebounced } from '@/composables/useDebounced'
import PageHeader from '@/components/PageHeader.vue'
import StateView from '@/components/StateView.vue'
import TeamCrest from '@/components/TeamCrest.vue'
import type { ApiError, Competition, Team } from '@/types'

const router = useRouter()
const { smAndDown } = useDisplay()

const headers = [
  { title: '', key: 'crest', sortable: false, width: 56 },
  { title: 'Equipo', key: 'name', sortable: false },
  { title: 'Sigla', key: 'tla', sortable: false },
  { title: 'País', key: 'area', sortable: false },
  { title: 'Fundado', key: 'founded', sortable: false },
  { title: 'Jugadores', key: 'players_count', sortable: false, align: 'end' as const },
]

const items = ref<Team[]>([])
const total = ref(0)
const loading = ref(false)
const error = ref<ApiError | null>(null)
const page = ref(1)
const perPage = ref(20)
const search = ref('')
const competitionId = ref<number | null>(null)
const competitions = ref<Competition[]>([])
const debouncedSearch = useDebounced(search)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await teamsApi.list({
      page: page.value,
      per_page: perPage.value,
      search: debouncedSearch.value || undefined,
      competition_id: competitionId.value ?? undefined,
    })
    items.value = res.data
    total.value = res.meta.total
  } catch (e) {
    error.value = toApiError(e)
  } finally {
    loading.value = false
  }
}

function onOptions(options: { page: number; itemsPerPage: number }) {
  page.value = options.page
  perPage.value = options.itemsPerPage
  load()
}

watch([debouncedSearch, competitionId], () => {
  page.value = 1
  load()
})

onMounted(async () => {
  // Solo para el filtro; si falla, la tabla sigue funcionando
  competitions.value = (await competitionsApi.list().catch(() => null))?.data.filter((c) => c.imported_at) ?? []
})
</script>

<template>
  <PageHeader title="Equipos" subtitle="Equipos guardados al importar ligas." />

  <v-row class="mb-2">
    <v-col cols="12" md="6">
      <v-text-field v-model="search" label="Buscar equipo" prepend-inner-icon="mdi-magnify" density="compact" hide-details clearable />
    </v-col>
    <v-col cols="12" md="6">
      <v-select
        v-model="competitionId"
        :items="competitions"
        item-title="name"
        item-value="id"
        label="Competición"
        density="compact"
        hide-details
        clearable
      />
    </v-col>
  </v-row>

  <StateView :error="error" @retry="load">
    <v-card>
      <v-data-table-server
        :headers="headers"
        :items="items"
        :items-length="total"
        :loading="loading"
        :page="page"
        :items-per-page="perPage"
        :mobile="smAndDown"
        hover
        no-data-text="Aún no hay equipos. Importa una liga desde Competiciones."
        loading-text="Cargando equipos..."
        @update:options="onOptions"
        @click:row="(_: unknown, row: { item: Team }) => router.push({ name: 'team', params: { id: row.item.id } })"
      >
        <template #[`item.crest`]="{ item }"><TeamCrest :src="item.crest" /></template>
        <template #[`item.founded`]="{ item }">{{ item.founded ?? '-' }}</template>
      </v-data-table-server>
    </v-card>
  </StateView>
</template>

<style scoped>
:deep(tbody tr) {
  cursor: pointer;
}
</style>
