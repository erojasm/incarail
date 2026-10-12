<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompetitionResource;
use App\Services\CompetitionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class CompetitionController extends Controller
{
    public function __construct(private readonly CompetitionService $competitions)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $result = $this->competitions->all();

        return CompetitionResource::collection($result->data)->additional(['meta' => $result->meta()]);
    }

    public function show(string $competitionId): JsonResponse
    {
        $result = $this->competitions->import($competitionId);

        return (new CompetitionResource($result->data))
            ->additional(['meta' => $result->meta()])
            ->response()
            ->setStatusCode(200);
    }
}
