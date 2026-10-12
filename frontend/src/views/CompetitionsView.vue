<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAbility } from '@casl/vue'
import { competitionsApi } from '@/services/api'
import { useAsync } from '@/composables/useAsync'
import PageHeader from '@/components/PageHeader.vue'
import StateView from '@/components/StateView.vue'
import TeamCrest from '@/components/TeamCrest.vue'

const { can } = useAbility()
const { data, loading, error, run } = useAsync(competitionsApi.list)
const search = ref('')

const competitions = computed(() => {
  const term = search.value.toLowerCase()
  return (data.value?.data ?? []).filter((c) =>
    [c.name, c.code, c.area].some((v) => v?.toLowerCase().includes(term)),
  )
})

onMounted(run)
</script>

<template>
  <PageHeader title="Competiciones" subtitle="Ligas disponibles en football-data. Al importar una liga se guardan sus equipos y jugadores.">
    <template #actions>
      <v-text-field
        v-model="search"
        placeholder="Buscar liga"
        prepend-inner-icon="mdi-magnify"
        density="compact"
        hide-details
        clearable
        style="min-width: 220px"
      />
    </template>
  </PageHeader>

  <v-alert v-if="data?.meta.warning" type="warning" variant="tonal" class="mb-4">{{ data.meta.warning }}</v-alert>

  <StateView :loading="loading" :error="error" :empty="!competitions.length" empty-text="No hay competiciones que coincidan." @retry="run">
    <v-row>
      <v-col v-for="c in competitions" :key="c.id" cols="12" sm="6" md="4" xl="3">
        <v-card class="h-100 d-flex flex-column">
          <v-card-item>
            <template #prepend><TeamCrest :src="c.emblem" :size="44" /></template>
            <v-card-title>{{ c.name }}</v-card-title>
            <v-card-subtitle>{{ c.area }} · {{ c.code }}</v-card-subtitle>
          </v-card-item>
          <v-card-text class="flex-grow-1">
            <v-chip v-if="c.imported_at" color="success" size="small" prepend-icon="mdi-check">
              Importada · {{ c.teams_count }} equipos
            </v-chip>
            <v-chip v-else size="small" variant="outlined">Sin importar</v-chip>
          </v-card-text>
          <v-card-actions v-if="can('import', 'Competition')">
            <v-btn
              :to="{ name: 'competition', params: { id: c.code ?? c.id } }"
              color="primary"
              variant="tonal"
              block
              :prepend-icon="c.imported_at ? 'mdi-eye-outline' : 'mdi-download'"
            >
              {{ c.imported_at ? 'Ver detalle' : 'Importar' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </StateView>
</template>
