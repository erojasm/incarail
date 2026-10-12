<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useAbility } from '@casl/vue'
import { navItems } from '@/router'
import { useAuthStore } from '@/stores/auth'
import { useNotifyStore } from '@/stores/notify'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const notify = useNotifyStore()
const { can } = useAbility()
const { mdAndUp } = useDisplay()

const drawer = ref(true)
const items = computed(() => navItems.filter((i) => can(i.can[0], i.can[1])))

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-app>
    <template v-if="route.meta.public">
      <v-main><router-view /></v-main>
    </template>

    <template v-else>
      <v-navigation-drawer v-model="drawer" :permanent="mdAndUp" :temporary="!mdAndUp">
        <div class="pa-4 d-flex align-center ga-2">
          <v-icon color="primary" icon="mdi-train" />
          <span class="text-title-medium font-weight-bold text-primary">Inca Rail Fútbol</span>
        </div>
        <v-divider />
        <v-list nav density="comfortable">
          <v-list-item
            v-for="item in items"
            :key="item.name"
            :to="{ name: item.name }"
            :prepend-icon="item.icon"
            :title="item.title"
            color="primary"
          />
        </v-list>
      </v-navigation-drawer>

      <v-app-bar color="primary" flat>
        <v-app-bar-nav-icon v-if="!mdAndUp" @click="drawer = !drawer" />
        <v-app-bar-title>{{ route.meta.title }}</v-app-bar-title>
        <v-menu>
          <template #activator="{ props }">
            <v-btn v-bind="props" variant="text" prepend-icon="mdi-account-circle">
              <span class="d-none d-sm-inline">{{ auth.user?.name }}</span>
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item :subtitle="auth.user?.email" :title="auth.user?.roles?.map((r) => r.label).join(', ')" />
            <v-divider />
            <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" @click="logout" />
          </v-list>
        </v-menu>
      </v-app-bar>

      <v-main>
        <v-container fluid class="pa-3 pa-md-6">
          <router-view />
        </v-container>
      </v-main>
    </template>

    <v-snackbar v-model="notify.visible" :color="notify.color" timeout="4000" location="bottom right">
      {{ notify.text }}
    </v-snackbar>
  </v-app>
</template>
