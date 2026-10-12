<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create(Arr::only($data, ['name', 'email', 'password', 'is_active']));
            $user->roles()->sync($data['roles'] ?? []);

            return $user->load('roles');
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        if ($user->is($actor) && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'No puedes desactivar tu propio usuario.']);
        }

        DB::transaction(function () use ($user, $data) {
            $user->fill(Arr::only(array_filter($data, fn ($v) => $v !== null), ['name', 'email', 'password', 'is_active']));
            $user->save();

            if (array_key_exists('roles', $data)) {
                $user->roles()->sync($data['roles']);
            }

            // Un usuario desactivado pierde sus sesiones abiertas
            if (! $user->is_active) {
                $user->tokens()->update(['revoked' => true]);
            }
        });

        return $user->load('roles');
    }
}
