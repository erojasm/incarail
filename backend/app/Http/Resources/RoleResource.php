<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'users_count' => $this->whenCounted('users'),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
