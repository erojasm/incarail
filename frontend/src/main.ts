import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { abilitiesPlugin } from '@casl/vue'

import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { ability } from './services/ability'
import { onUnauthorized } from './services/http'
import { useAuthStore } from './stores/auth'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)
app.use(abilitiesPlugin, ability, { useGlobalProperties: true })

// Token vencido o revocado -> login con aviso
onUnauthorized(() => {
  useAuthStore().clear()
  if (router.currentRoute.value.name !== 'login') {
    router.push({ name: 'login', query: { expired: '1' } })
  }
})

app.mount('#app')
