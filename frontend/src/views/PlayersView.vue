<script setup lang="ts">
import { ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import { useAbility } from '@casl/vue'
import { playersApi } from '@/services/api'
import { toApiError } from '@/services/http'
import { useDebounced } from '@/composables/useDebounced'
import PageHeader from '@/components/PageHeader.vue'
import StateView from '@/components/StateView.vue'
import TeamCrest from '@/components/TeamCrest.vue'
import type { ApiError, Player } from '@/types'

const { smAndDown } = useDisplay()
const { can } = useAbility()

const positions = ['Goalkeeper', 'Defence', 'Midfield', 'Offence']
const headers = [
  { title: 'N.º', key: 'shirt_number', sortable: false, width: 70 },
  { title: 'Nombre', key: 'name', sortable: false },
  { title: 'Posición', key: 'position', sortable: false },
  { title: 'Nacionalidad', key: 'nationality', sortable: false },
  { title: 'Equipo', key: 'team', sortable: false },
]

const items = ref<Player[]>([])
const total = ref(0)
const loading = ref(false)
const error = ref<ApiError | null>(null)
const page = ref(1)
const perPage = ref(20)
const search = ref('')
const position = ref<string | null>(null)
const debouncedSearch = useDebounced(search)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await playersApi.list({
      page: page.value,
      per_page: perPage.value,
      search: debouncedSearch.value || undefined,
      position: position.value ?? undefined,
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

watch([debouncedSearch, position], () => {
  page.value = 1
  load()
})
</script>

<template>
  <PageHeader title="Jugadores" subtitle="Jugadores guardados al importar ligas." />

  <v-row class="mb-2">
    <v-col cols="12" md="8">
      <v-text-field v-model="search" label="Buscar jugador" prepend-inner-icon="mdi-magnify" density="compact" hide-details clearable />
    </v-col>
    <v-col cols="12" md="4">
      <v-select v-model="position" :items="positions" label="Posición" density="compact" hide-details clearable />
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
        no-data-text="Aún no hay jugadores. Importa una liga desde Competiciones."
        loading-text="Cargando jugadores..."
        @update:options="onOptions"
      >
        <template #[`item.shirt_number`]="{ item }">{{ item.shirt_number ?? '-' }}</template>
        <template #[`item.team`]="{ item }">
          <router-link
            v-if="item.team && can('read', 'Team')"
            :to="{ name: 'team', params: { id: item.team.id } }"
            class="d-inline-flex align-center ga-2"
          >
            <TeamCrest :src="item.team.crest" :size="24" /> {{ item.team.short_name ?? item.team.name }}
          </router-link>
          <span v-else>{{ item.team?.name ?? '-' }}</span>
        </template>
      </v-data-table-server>
    </v-card>
  </StateView>
</template>
