import { http } from './http'
import type {
  Competition, ListParams, LoginResponse, Paginated, Permission, Player, Role, Session, SyncMeta, Team, User,
} from '@/types'

type WithMeta<T> = { data: T; meta: SyncMeta }

export interface UserPayload {
  name?: string
  email?: string
  password?: string | null
  is_active?: boolean
  roles?: number[]
}

export interface RolePayload {
  name: string
  label: string
  permissions: number[]
}

export const authApi = {
  login: (email: string, password: string) => http.post<LoginResponse>('/login', { email, password }).then((r) => r.data),
  me: () => http.get<Session>('/me').then((r) => r.data),
  logout: () => http.post('/logout'),
}

export const competitionsApi = {
  list: () => http.get<WithMeta<Competition[]>>('/competitions').then((r) => r.data),
  import: (id: string | number) => http.get<WithMeta<Competition>>(`/competitions/${id}`).then((r) => r.data),
}

export const teamsApi = {
  list: (params: ListParams) => http.get<Paginated<Team>>('/team', { params }).then((r) => r.data),
  show: (id: string | number) => http.get<{ data: Team }>(`/team/${id}`).then((r) => r.data.data),
}

export const playersApi = {
  list: (params: ListParams) => http.get<Paginated<Player>>('/players', { params }).then((r) => r.data),
}

export const usersApi = {
  list: (params: ListParams) => http.get<Paginated<User>>('/users', { params }).then((r) => r.data),
  create: (payload: UserPayload) => http.post<{ data: User }>('/users', payload).then((r) => r.data.data),
  update: (id: number, payload: UserPayload) => http.put<{ data: User }>(`/users/${id}`, payload).then((r) => r.data.data),
}

export const rolesApi = {
  list: () => http.get<{ data: Role[] }>('/roles').then((r) => r.data.data),
  create: (payload: RolePayload) => http.post<{ data: Role }>('/roles', payload).then((r) => r.data.data),
  update: (id: number, payload: RolePayload) => http.put<{ data: Role }>(`/roles/${id}`, payload).then((r) => r.data.data),
  remove: (id: number) => http.delete(`/roles/${id}`),
}

export const permissionsApi = {
  list: () => http.get<{ data: Permission[] }>('/permissions').then((r) => r.data.data),
}
