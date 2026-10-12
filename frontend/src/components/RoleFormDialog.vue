<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { rolesApi } from '@/services/api'
import { toApiError } from '@/services/http'
import { useNotifyStore } from '@/stores/notify'
import type { Permission, Role } from '@/types'

const open = defineModel<boolean>({ required: true })
const props = defineProps<{ role: Role | null; permissions: Permission[] }>()
const emit = defineEmits<{ saved: [Role] }>()

const notify = useNotifyStore()
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})
const form = reactive({ name: '', label: '', permissions: [] as number[] })

const isAdmin = computed(() => props.role?.name === 'admin')

// Permisos agrupados por sujeto (Competition, Team, ...)
const groups = computed(() => {
  const map = new Map<string, Permission[]>()
  props.permissions.forEach((p) => map.set(p.subject, [...(map.get(p.subject) ?? []), p]))
  return [...map.entries()]
})

watch(open, (value) => {
  if (!value) return
  errors.value = {}
  Object.assign(form, {
    name: props.role?.name ?? '',
    label: props.role?.label ?? '',
    permissions: props.role?.permissions?.map((p) => p.id) ?? [],
  })
})

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const role = props.role ? await rolesApi.update(props.role.id, form) : await rolesApi.create(form)
    notify.success(props.role ? 'Rol actualizado.' : 'Rol creado.')
    emit('saved', role)
    open.value = false
  } catch (e) {
    const err = toApiError(e)
    if (err.status === 422) errors.value = err.errors ?? {}
    else notify.error(err.message)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <v-dialog v-model="open" max-width="600" :persistent="saving">
    <v-card :title="role ? 'Editar rol' : 'Nuevo rol'">
      <v-card-text>
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field v-model="form.name" label="Clave" hint="Ej: analista" :disabled="isAdmin" :error-messages="errors.name" />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field v-model="form.label" label="Nombre visible" :error-messages="errors.label" />
          </v-col>
        </v-row>

        <v-alert v-if="isAdmin" type="info" variant="tonal" density="compact" class="mb-3">
          El rol administrador siempre tiene todos los permisos.
        </v-alert>

        <div v-for="[subject, items] in groups" :key="subject" class="mb-2">
          <p class="text-title-small mb-1">{{ subject }}</p>
          <v-checkbox
            v-for="p in items"
            :key="p.id"
            v-model="form.permissions"
            :value="p.id"
            :label="p.description ?? p.name"
            :disabled="isAdmin"
            density="compact"
            hide-details
          />
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="open = false">Cancelar</v-btn>
        <v-btn color="primary" variant="flat" :loading="saving" @click="save">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
