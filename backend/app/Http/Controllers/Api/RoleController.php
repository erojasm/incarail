<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(Role::with('permissions')->withCount('users')->orderBy('label')->get());
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->roles->save(new Role, $request->validated());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function update(RoleRequest $request, Role $role): RoleResource
    {
        return new RoleResource($this->roles->save($role, $request->validated()));
    }

    public function destroy(Role $role): Response
    {
        $this->roles->delete($role);

        return response()->noContent();
    }
}
