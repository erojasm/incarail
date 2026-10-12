<?php

namespace App\Services;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }

        if (! $user->is_active) {
            throw new AccessDeniedHttpException('Tu usuario está desactivado. Contacta al administrador.');
        }

        return [
            'token_type' => 'Bearer',
            'access_token' => $user->createToken('spa')->accessToken,
            'expires_in' => AppServiceProvider::TOKEN_HOURS * 3600,
            'user' => $user,
        ];
    }

    public function logout(User $user): void
    {
        $user->token()?->revoke();
    }
}
