<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function save(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $isAdmin = $role->name === Role::ADMIN;

            // El rol admin siempre conserva su nombre y todos los permisos
            $role->fill(['name' => $isAdmin ? Role::ADMIN : $data['name'], 'label' => $data['label']])->save();
            $role->permissions()->sync($isAdmin ? Permission::pluck('id') : ($data['permissions'] ?? []));

            return $role->load('permissions')->loadCount('users');
        });
    }

    public function delete(Role $role): void
    {
        if ($role->name === Role::ADMIN || $role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'No se puede eliminar el rol administrador ni un rol con usuarios asignados.']);
        }

        $role->delete();
    }
}
