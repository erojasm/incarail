<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { rolesApi, permissionsApi } from '@/services/api'
import { toApiError } from '@/services/http'
import { useAsync } from '@/composables/useAsync'
import { useAuthStore } from '@/stores/auth'
import { useNotifyStore } from '@/stores/notify'
import PageHeader from '@/components/PageHeader.vue'
import StateView from '@/components/StateView.vue'
import RoleFormDialog from '@/components/RoleFormDialog.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import type { Permission, Role } from '@/types'

const auth = useAuthStore()
const notify = useNotifyStore()
const { data: roles, loading, error, run: load } = useAsync(rolesApi.list)

const permissions = ref<Permission[]>([])
const dialog = ref(false)
const editing = ref<Role | null>(null)
const toDelete = ref<Role | null>(null)
const confirmOpen = ref(false)
const deleting = ref(false)

function openForm(role: Role | null = null) {
  editing.value = role
  dialog.value = true
}

function askDelete(role: Role) {
  toDelete.value = role
  confirmOpen.value = true
}

async function remove() {
  if (!toDelete.value) return
  deleting.value = true
  try {
    await rolesApi.remove(toDelete.value.id)
    notify.success('Rol eliminado.')
    confirmOpen.value = false
    load()
  } catch (e) {
    const err = toApiError(e)
    notify.error(err.errors?.role?.[0] ?? err.message)
  } finally {
    deleting.value = false
  }
}

// Si edito mis propios roles, refresco mis permisos
async function onSaved() {
  await load()
  await auth.fetchMe()
}

onMounted(async () => {
  load()
  permissions.value = await permissionsApi.list().catch(() => [])
})
</script>

<template>
  <PageHeader title="Roles y permisos" subtitle="Define qué puede hacer cada rol. Los permisos se validan en el servidor.">
    <template #actions>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openForm()">Nuevo rol</v-btn>
    </template>
  </PageHeader>

  <StateView :loading="loading" :error="error" :empty="!roles?.length" empty-text="No hay roles." @retry="load">
    <v-row>
      <v-col v-for="role in roles" :key="role.id" cols="12" md="6" lg="4">
        <v-card class="h-100 d-flex flex-column">
          <v-card-item>
            <v-card-title>{{ role.label }}</v-card-title>
            <v-card-subtitle>{{ role.name }} · {{ role.users_count ?? 0 }} usuarios</v-card-subtitle>
          </v-card-item>
          <v-card-text class="flex-grow-1">
            <v-chip v-for="p in role.permissions" :key="p.id" size="small" class="mr-1 mb-1">{{ p.description ?? p.name }}</v-chip>
            <span v-if="!role.permissions?.length" class="text-medium-emphasis">Sin permisos</span>
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" prepend-icon="mdi-pencil" @click="openForm(role)">Editar</v-btn>
            <v-btn
              variant="text"
              color="error"
              prepend-icon="mdi-delete-outline"
              :disabled="role.name === 'admin' || (role.users_count ?? 0) > 0"
              @click="askDelete(role)"
            >
              Eliminar
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </StateView>

  <RoleFormDialog v-model="dialog" :role="editing" :permissions="permissions" @saved="onSaved" />
  <ConfirmDialog
    v-model="confirmOpen"
    title="Eliminar rol"
    :text="`¿Seguro que quieres eliminar el rol «${toDelete?.label}»?`"
    confirm-text="Eliminar"
    :loading="deleting"
    @confirm="remove"
  />
</template>
