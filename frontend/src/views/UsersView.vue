<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import { usersApi, rolesApi } from '@/services/api'
import { toApiError } from '@/services/http'
import { useDebounced } from '@/composables/useDebounced'
import { useAuthStore } from '@/stores/auth'
import { useNotifyStore } from '@/stores/notify'
import PageHeader from '@/components/PageHeader.vue'
import StateView from '@/components/StateView.vue'
import UserFormDialog from '@/components/UserFormDialog.vue'
import type { ApiError, Role, User } from '@/types'

const { smAndDown } = useDisplay()
const auth = useAuthStore()
const notify = useNotifyStore()

const headers = [
  { title: 'Nombre', key: 'name', sortable: false },
  { title: 'Correo', key: 'email', sortable: false },
  { title: 'Roles', key: 'roles', sortable: false },
  { title: 'Activo', key: 'is_active', sortable: false },
  { title: '', key: 'actions', sortable: false, align: 'end' as const },
]

const items = ref<User[]>([])
const roles = ref<Role[]>([])
const total = ref(0)
const loading = ref(false)
const error = ref<ApiError | null>(null)
const page = ref(1)
const perPage = ref(20)
const search = ref('')
const debouncedSearch = useDebounced(search)
const dialog = ref(false)
const editing = ref<User | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await usersApi.list({ page: page.value, per_page: perPage.value, search: debouncedSearch.value || undefined })
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

function openForm(user: User | null = null) {
  editing.value = user
  dialog.value = true
}

async function toggleActive(user: User, value: boolean | null) {
  try {
    const updated = await usersApi.update(user.id, { is_active: !!value })
    Object.assign(user, updated)
    notify.success(updated.is_active ? 'Usuario activado.' : 'Usuario desactivado.')
  } catch (e) {
    const err = toApiError(e)
    notify.error(err.errors?.is_active?.[0] ?? err.message)
  }
}

watch(debouncedSearch, () => {
  page.value = 1
  load()
})

onMounted(async () => {
  roles.value = await rolesApi.list().catch(() => [])
})
</script>

<template>
  <PageHeader title="Usuarios" subtitle="Crea, edita, asigna roles y desactiva usuarios.">
    <template #actions>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openForm()">Nuevo usuario</v-btn>
    </template>
  </PageHeader>

  <v-text-field v-model="search" label="Buscar por nombre o correo" prepend-inner-icon="mdi-magnify" density="compact" hide-details clearable class="mb-4" />

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
        no-data-text="No hay usuarios."
        @update:options="onOptions"
      >
        <template #[`item.roles`]="{ item }">
          <v-chip v-for="r in item.roles" :key="r.id" size="small" class="mr-1">{{ r.label }}</v-chip>
        </template>
        <template #[`item.is_active`]="{ item }">
          <v-switch
            :model-value="item.is_active"
            :disabled="item.id === auth.user?.id"
            color="primary"
            density="compact"
            hide-details
            inset
            @update:model-value="(v: boolean | null) => toggleActive(item, v)"
          />
        </template>
        <template #[`item.actions`]="{ item }">
          <v-btn icon="mdi-pencil" variant="text" size="small" aria-label="Editar" @click="openForm(item)" />
        </template>
      </v-data-table-server>
    </v-card>
  </StateView>

  <UserFormDialog v-model="dialog" :user="editing" :roles="roles" @saved="load" />
</template>
