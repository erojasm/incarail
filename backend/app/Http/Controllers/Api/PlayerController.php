<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlayerIndexRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlayerController extends Controller
{
    public function index(PlayerIndexRequest $request): AnonymousResourceCollection
    {
        $players = Player::query()
            ->with('team')
            ->search($request->validated('search'))
            ->filter($request->validated())
            ->orderBy('name')
            ->paginate($request->validated('per_page', 20));

        return PlayerResource::collection($players);
    }
}
