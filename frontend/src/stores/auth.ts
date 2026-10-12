import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '@/services/api'
import { ability } from '@/services/ability'
import { tokenStorage } from '@/services/http'
import type { Rule, User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(tokenStorage.get())
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  function applySession(sessionUser: User, rules: Rule[]) {
    user.value = sessionUser
    ability.update(rules)
  }

  async function login(email: string, password: string) {
    const session = await authApi.login(email, password)
    token.value = session.access_token
    tokenStorage.set(session.access_token)
    applySession(session.user, session.rules)
  }

  async function fetchMe() {
    const session = await authApi.me()
    applySession(session.user, session.rules)
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // si el token ya no sirve igual cerramos sesión local
    } finally {
      clear()
    }
  }

  function clear() {
    token.value = null
    user.value = null
    tokenStorage.clear()
    ability.update([])
  }

  return { token, user, isAuthenticated, login, fetchMe, logout, clear }
})
