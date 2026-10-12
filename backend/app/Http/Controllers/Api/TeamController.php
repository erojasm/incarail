<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamIndexRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Solo lee la BD local, nunca llama a football-data
class TeamController extends Controller
{
    public function index(TeamIndexRequest $request): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->search($request->validated('search'))
            ->inCompetition($request->validated('competition_id'))
            ->withCount('players')
            ->orderBy('name')
            ->paginate($request->validated('per_page', 20));

        return TeamResource::collection($teams);
    }

    public function show(int $teamId): TeamResource
    {
        $team = Team::with([
            'players' => fn ($q) => $q->orderBy('position')->orderBy('name'),
            'competitions',
        ])->withCount('players')->findOrFail($teamId);

        return new TeamResource($team);
    }
}
