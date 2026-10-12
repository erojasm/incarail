<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->login($request->validated('email'), $request->validated('password'));

        return response()->json([
            ...$session,
            ...$this->profile($session['user']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->user());

        return response()->noContent();
    }

    // Usuario + reglas CASL
    private function profile(User $user): array
    {
        return [
            'user' => new UserResource($user->load('roles')),
            'rules' => $user->abilityRules(),
        ];
    }
}
