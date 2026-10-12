<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { teamsApi } from '@/services/api'
import { useAsync } from '@/composables/useAsync'
import StateView from '@/components/StateView.vue'
import TeamCrest from '@/components/TeamCrest.vue'

const route = useRoute()
const { data: team, loading, error, run } = useAsync(() => teamsApi.show(String(route.params.id)))

const headers = [
  { title: 'N.º', key: 'shirt_number', width: 70 },
  { title: 'Nombre', key: 'name' },
  { title: 'Posición', key: 'position' },
  { title: 'Nacionalidad', key: 'nationality' },
  { title: 'Nacimiento', key: 'date_of_birth' },
]

const details = computed(() => [
  { icon: 'mdi-stadium', label: 'Estadio', value: team.value?.venue },
  { icon: 'mdi-calendar', label: 'Fundado', value: team.value?.founded },
  { icon: 'mdi-palette', label: 'Colores', value: team.value?.club_colors },
  { icon: 'mdi-whistle', label: 'Entrenador', value: team.value?.coach },
  { icon: 'mdi-map-marker', label: 'País', value: team.value?.area },
])

onMounted(run)
</script>

<template>
  <v-btn :to="{ name: 'teams' }" variant="text" prepend-icon="mdi-arrow-left" class="mb-2">Equipos</v-btn>

  <StateView :loading="loading" :error="error" @retry="run">
    <template v-if="team">
      <v-card class="mb-4">
        <v-card-item>
          <template #prepend><TeamCrest :src="team.crest" :size="72" /></template>
          <v-card-title class="text-headline-small">{{ team.name }}</v-card-title>
          <v-card-subtitle>
            {{ team.short_name }} · {{ team.tla }}
            <a v-if="team.website" :href="team.website" target="_blank" rel="noopener" class="ml-2">Sitio web</a>
          </v-card-subtitle>
        </v-card-item>
        <v-card-text>
          <v-row dense>
            <v-col v-for="d in details" :key="d.label" cols="12" sm="6" md="4">
              <v-icon :icon="d.icon" size="small" class="mr-1" />
              <strong>{{ d.label }}:</strong> {{ d.value ?? '-' }}
            </v-col>
          </v-row>
          <div class="mt-3 d-flex flex-wrap ga-2">
            <v-chip v-for="c in team.competitions" :key="c.id" size="small" prepend-icon="mdi-trophy-outline">{{ c.name }}</v-chip>
          </div>
        </v-card-text>
      </v-card>

      <v-card title="Plantel">
        <v-data-table
          :headers="headers"
          :items="team.players ?? []"
          :items-per-page="-1"
          hide-default-footer
          no-data-text="Este equipo no tiene jugadores guardados."
        >
          <template #[`item.shirt_number`]="{ item }">{{ item.shirt_number ?? '-' }}</template>
          <template #[`item.date_of_birth`]="{ item }">{{ item.date_of_birth ?? '-' }}</template>
        </v-data-table>
      </v-card>
    </template>
  </StateView>
</template>
