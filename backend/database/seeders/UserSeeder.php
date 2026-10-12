<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Administrador', 'admin@incarail.test', 'admin'],
            ['Operador', 'operador@incarail.test', 'operador'],
            ['Consulta', 'consulta@incarail.test', 'consulta'],
        ];

        foreach ($users as [$name, $email, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'is_active' => true],
            );

            $user->roles()->sync(Role::where('name', $role)->pluck('id'));
        }
    }
}
