export interface Rule {
  action: string
  subject: string
}

export interface Permission {
  id: number
  name: string
  action: string
  subject: string
  description: string | null
}

export interface Role {
  id: number
  name: string
  label: string
  users_count?: number
  permissions?: Permission[]
}

export interface User {
  id: number
  name: string
  email: string
  is_active: boolean
  roles?: Role[]
}

export interface Competition {
  id: number
  code: string | null
  name: string
  type: string | null
  emblem: string | null
  area: string | null
  plan: string | null
  season: { start: string | null; end: string | null }
  imported_at: string | null
  teams_count?: number
  teams?: Team[]
}

export interface Team {
  id: number
  name: string
  short_name: string | null
  tla: string | null
  crest: string | null
  venue: string | null
  founded: number | null
  club_colors: string | null
  website: string | null
  area: string | null
  coach: string | null
  players_count?: number
  players?: Player[]
  competitions?: Pick<Competition, 'id' | 'code' | 'name' | 'emblem'>[]
}

export interface Player {
  id: number
  name: string
  position: string | null
  shirt_number: number | null
  date_of_birth: string | null
  nationality: string | null
  team?: Pick<Team, 'id' | 'name' | 'short_name' | 'crest'> | null
}

export interface SyncMeta {
  source: 'database' | 'football-data'
  warning: string | null
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export interface ApiError {
  status: number
  code: string
  message: string
  errors?: Record<string, string[]>
  retry_after?: number
}

export interface Session {
  user: User
  rules: Rule[]
}

export interface LoginResponse extends Session {
  token_type: 'Bearer'
  access_token: string
  expires_in: number
}

export interface ListParams {
  page?: number
  per_page?: number
  search?: string
  [key: string]: string | number | undefined
}
