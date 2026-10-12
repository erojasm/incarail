<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    // [nombre, acción, sujeto, descripción]
    private const PERMISSIONS = [
        ['competitions.read', 'read', 'Competition', 'Ver competiciones'],
        ['competitions.import', 'import', 'Competition', 'Importar una competición desde football-data'],
        ['teams.read', 'read', 'Team', 'Ver equipos'],
        ['players.read', 'read', 'Player', 'Ver jugadores'],
        ['users.manage', 'manage', 'User', 'Gestionar usuarios'],
        ['roles.manage', 'manage', 'Role', 'Gestionar roles y permisos'],
    ];

    private const ROLES = [
        'admin' => ['Administrador', '*'],
        'operador' => ['Operador', ['competitions.read', 'competitions.import', 'teams.read', 'players.read']],
        'consulta' => ['Solo consulta', ['competitions.read', 'teams.read', 'players.read']],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as [$name, $action, $subject, $description]) {
            Permission::updateOrCreate(['name' => $name], compact('action', 'subject', 'description'));
        }

        foreach (self::ROLES as $name => [$label, $permissions]) {
            $role = Role::updateOrCreate(['name' => $name], ['label' => $label]);

            $ids = $permissions === '*'
                ? Permission::pluck('id')
                : Permission::whereIn('name', $permissions)->pluck('id');

            $role->permissions()->sync($ids);
        }
    }
}
