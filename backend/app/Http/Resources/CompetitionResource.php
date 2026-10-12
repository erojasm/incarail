<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'emblem' => $this->emblem,
            'area' => $this->area_name,
            'plan' => $this->plan,
            'season' => [
                'start' => $this->season_start?->toDateString(),
                'end' => $this->season_end?->toDateString(),
            ],
            'imported_at' => $this->imported_at?->toIso8601String(),
            'teams_count' => $this->whenCounted('teams'),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
        ];
    }
}
