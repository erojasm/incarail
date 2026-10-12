<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAbility } from '@casl/vue'
import { competitionsApi } from '@/services/api'
import { useAsync } from '@/composables/useAsync'
import PageHeader from '@/components/PageHeader.vue'
import StateView from '@/components/StateView.vue'
import TeamCrest from '@/components/TeamCrest.vue'

const route = useRoute()
const { can } = useAbility()
const { data, loading, error, run } = useAsync(() => competitionsApi.import(String(route.params.id)))

onMounted(run)
</script>

<template>
  <v-btn :to="{ name: 'competitions' }" variant="text" prepend-icon="mdi-arrow-left" class="mb-2">Competiciones</v-btn>

  <StateView
    :loading="loading"
    :error="error"
    loading-text="Consultando la liga (puede tardar si se importa desde football-data)..."
    @retry="run"
  >
    <template v-if="data">
      <PageHeader :title="data.data.name" :subtitle="`${data.data.area ?? ''} · Temporada ${data.data.season.start ?? '-'} a ${data.data.season.end ?? '-'}`">
        <template #actions>
          <v-chip :color="data.meta.source === 'football-data' ? 'success' : 'secondary'" prepend-icon="mdi-database">
            {{ data.meta.source === 'football-data' ? 'Recién importada' : 'Desde datos guardados' }}
          </v-chip>
        </template>
      </PageHeader>

      <v-alert v-if="data.meta.warning" type="warning" variant="tonal" class="mb-4">{{ data.meta.warning }}</v-alert>

      <StateView :empty="!data.data.teams?.length" empty-text="Esta liga no tiene equipos.">
        <v-row>
          <v-col v-for="team in data.data.teams" :key="team.id" cols="12" sm="6" md="4" lg="3">
            <v-card :to="can('read', 'Team') ? { name: 'team', params: { id: team.id } } : undefined" class="h-100">
              <v-card-item>
                <template #prepend><TeamCrest :src="team.crest" :size="40" /></template>
                <v-card-title class="text-body-large">{{ team.name }}</v-card-title>
                <v-card-subtitle>{{ team.players_count ?? 0 }} jugadores · {{ team.venue ?? 'Sin estadio' }}</v-card-subtitle>
              </v-card-item>
            </v-card>
          </v-col>
        </v-row>
      </StateView>
    </template>
  </StateView>
</template>
