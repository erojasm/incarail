import { createRouter, createWebHistory, type RouteLocationRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ability } from '@/services/ability'

declare module 'vue-router' {
  interface RouteMeta {
    public?: boolean
    title?: string
    can?: [string, string]
  }
}

export const navItems = [
  { name: 'competitions', title: 'Competiciones', icon: 'mdi-trophy-outline', can: ['read', 'Competition'] },
  { name: 'teams', title: 'Equipos', icon: 'mdi-shield-half-full', can: ['read', 'Team'] },
  { name: 'players', title: 'Jugadores', icon: 'mdi-account-group-outline', can: ['read', 'Player'] },
  { name: 'users', title: 'Usuarios', icon: 'mdi-account-cog-outline', can: ['manage', 'User'] },
  { name: 'roles', title: 'Roles y permisos', icon: 'mdi-shield-key-outline', can: ['manage', 'Role'] },
] as const

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { public: true, title: 'Iniciar sesión' } },
    { path: '/', name: 'home', component: { render: () => null } },
    { path: '/competitions', name: 'competitions', component: () => import('@/views/CompetitionsView.vue'), meta: { title: 'Competiciones', can: ['read', 'Competition'] } },
    { path: '/competitions/:id', name: 'competition', component: () => import('@/views/CompetitionDetailView.vue'), meta: { title: 'Competición', can: ['import', 'Competition'] } },
    { path: '/teams', name: 'teams', component: () => import('@/views/TeamsView.vue'), meta: { title: 'Equipos', can: ['read', 'Team'] } },
    { path: '/teams/:id', name: 'team', component: () => import('@/views/TeamDetailView.vue'), meta: { title: 'Equipo', can: ['read', 'Team'] } },
    { path: '/players', name: 'players', component: () => import('@/views/PlayersView.vue'), meta: { title: 'Jugadores', can: ['read', 'Player'] } },
    { path: '/users', name: 'users', component: () => import('@/views/UsersView.vue'), meta: { title: 'Usuarios', can: ['manage', 'User'] } },
    { path: '/roles', name: 'roles', component: () => import('@/views/RolesView.vue'), meta: { title: 'Roles y permisos', can: ['manage', 'Role'] } },
    { path: '/forbidden', name: 'forbidden', component: () => import('@/views/ForbiddenView.vue'), meta: { title: 'Sin permiso' } },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue'), meta: { title: 'No encontrado' } },
  ],
})

// Primera pantalla a la que el usuario tiene acceso
function firstAllowedRoute(): RouteLocationRaw {
  const item = navItems.find((i) => ability.can(i.can[0], i.can[1]))
  return item ? { name: item.name } : { name: 'forbidden' }
}

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    return auth.isAuthenticated && to.name === 'login' ? { name: 'home' } : true
  }

  if (!auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (!auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      auth.clear()
      return { name: 'login', query: { expired: '1' } }
    }
  }

  if (to.name === 'home') return firstAllowedRoute()

  if (to.meta.can && ability.cannot(...to.meta.can)) {
    return { name: 'forbidden' }
  }

  return true
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · Inca Rail Fútbol` : 'Inca Rail Fútbol'
})

export default router
