<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { usersApi } from '@/services/api'
import { toApiError } from '@/services/http'
import { useNotifyStore } from '@/stores/notify'
import type { Role, User } from '@/types'

const open = defineModel<boolean>({ required: true })
const props = defineProps<{ user: User | null; roles: Role[] }>()
const emit = defineEmits<{ saved: [User] }>()

const notify = useNotifyStore()
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})
const form = reactive({ name: '', email: '', password: '', is_active: true, roles: [] as number[] })

watch(open, (value) => {
  if (!value) return
  errors.value = {}
  Object.assign(form, {
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    is_active: props.user?.is_active ?? true,
    roles: props.user?.roles?.map((r) => r.id) ?? [],
  })
})

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = { ...form, password: form.password || null }
    const user = props.user ? await usersApi.update(props.user.id, payload) : await usersApi.create(payload)
    notify.success(props.user ? 'Usuario actualizado.' : 'Usuario creado.')
    emit('saved', user)
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
  <v-dialog v-model="open" max-width="520" :persistent="saving">
    <v-card :title="user ? 'Editar usuario' : 'Nuevo usuario'">
      <v-card-text class="d-flex flex-column ga-2">
        <v-text-field v-model="form.name" label="Nombre" :error-messages="errors.name" />
        <v-text-field v-model="form.email" label="Correo" type="email" :error-messages="errors.email" />
        <v-text-field
          v-model="form.password"
          label="Contraseña"
          type="password"
          :hint="user ? 'Déjala vacía para no cambiarla' : 'Mínimo 8 caracteres'"
          persistent-hint
          :error-messages="errors.password"
        />
        <v-select
          v-model="form.roles"
          :items="roles"
          item-title="label"
          item-value="id"
          label="Roles"
          multiple
          chips
          :error-messages="errors.roles"
        />
        <v-switch v-model="form.is_active" label="Activo" color="primary" inset :error-messages="errors.is_active" />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="open = false">Cancelar</v-btn>
        <v-btn color="primary" variant="flat" :loading="saving" @click="save">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
