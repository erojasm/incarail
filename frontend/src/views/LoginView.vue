<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/services/http'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const error = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})
const expired = route.query.expired === '1'

async function submit() {
  loading.value = true
  error.value = null
  fieldErrors.value = {}
  try {
    await auth.login(form.email, form.password)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    router.push(redirect)
  } catch (e) {
    const err = toApiError(e)
    if (err.status === 422) fieldErrors.value = err.errors ?? {}
    else error.value = err.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-bg d-flex align-center justify-center pa-4">
    <v-card class="pa-6 w-100" max-width="420" elevation="8">
      <div class="text-center mb-6">
        <v-icon icon="mdi-train" size="40" color="primary" />
        <h1 class="text-headline-small mt-2">Inca Rail Fútbol</h1>
        <p class="text-body-medium text-medium-emphasis">Ingresa con tu cuenta</p>
      </div>

      <v-alert v-if="expired && !error" type="warning" variant="tonal" density="compact" class="mb-4">
        Tu sesión expiró. Vuelve a iniciar sesión.
      </v-alert>
      <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">{{ error }}</v-alert>

      <v-form @submit.prevent="submit">
        <v-text-field
          v-model="form.email"
          label="Correo"
          type="email"
          prepend-inner-icon="mdi-email-outline"
          autocomplete="username"
          :error-messages="fieldErrors.email"
        />
        <v-text-field
          v-model="form.password"
          label="Contraseña"
          type="password"
          prepend-inner-icon="mdi-lock-outline"
          autocomplete="current-password"
          :error-messages="fieldErrors.password"
        />
        <v-btn type="submit" color="primary" block size="large" :loading="loading">Ingresar</v-btn>
      </v-form>
    </v-card>
  </div>
</template>

<style scoped>
.login-bg {
  min-height: 100vh;
  background: linear-gradient(160deg, #0b3d2e 0%, #134e3c 100%);
}
</style>
