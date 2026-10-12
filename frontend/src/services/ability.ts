import { createMongoAbility, type MongoAbility } from '@casl/ability'

export type AppAbility = MongoAbility<[string, string]>

// Se llena con las reglas que entrega /api/me
export const ability = createMongoAbility<[string, string]>([])
