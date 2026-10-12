<?php

namespace App\Services;

use App\Exceptions\FootballDataException;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use App\Services\FootballData\FootballDataClient;
use App\Support\SyncResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CompetitionService
{
    private const LIST_SYNCED_KEY = 'competitions:list-synced';

    public function __construct(private readonly FootballDataClient $client) {}

    // Lista desde la BD; solo va a football-data si la copia local venció
    public function all(): SyncResult
    {
        $source = 'database';
        $warning = null;

        if (Cache::missing(self::LIST_SYNCED_KEY) || Competition::doesntExist()) {
            try {
                $this->syncList();
                $source = 'football-data';
            } catch (FootballDataException $e) {
                if (Competition::doesntExist()) {
                    throw $e;
                }
                $warning = $e->getMessage().' Se muestran los datos guardados.';
            }
        }

        $competitions = Competition::withCount('teams')->orderBy('area_name')->orderBy('name')->get();

        return new SyncResult($competitions, $source, $warning);
    }

    // Importa (o refresca) una liga con sus equipos y jugadores
    public function import(string $idOrCode): SyncResult
    {
        $local = $this->findLocal($idOrCode);

        if ($local?->imported_at?->gt(now()->subMinutes($this->ttl()))) {
            return new SyncResult($this->withTeams($local), 'database');
        }

        try {
            $payload = $this->client->competitionTeams($local?->code ?? strtoupper($idOrCode));
        } catch (FootballDataException $e) {
            if ($local?->imported_at) {
                return new SyncResult($this->withTeams($local), 'database', $e->getMessage().' Se muestran los datos guardados.');
            }
            throw $e;
        }

        return new SyncResult($this->withTeams($this->persist($payload)), 'football-data');
    }

    private function syncList(): void
    {
        $rows = collect($this->client->competitions())->map(fn (array $c) => [
            'id' => $c['id'],
            'code' => $c['code'] ?? null,
            'name' => $c['name'],
            'type' => $c['type'] ?? null,
            'emblem' => $c['emblem'] ?? null,
            'area_name' => $c['area']['name'] ?? null,
            'plan' => $c['plan'] ?? null,
            'season_start' => $c['currentSeason']['startDate'] ?? null,
            'season_end' => $c['currentSeason']['endDate'] ?? null,
        ])->all();

        Competition::upsert($rows, ['id'], ['code', 'name', 'type', 'emblem', 'area_name', 'plan', 'season_start', 'season_end']);
        Cache::put(self::LIST_SYNCED_KEY, true, now()->addMinutes($this->ttl()));
    }

    // Upsert por id de football-data: importar dos veces no duplica
    private function persist(array $payload): Competition
    {
        return DB::transaction(function () use ($payload) {
            $c = $payload['competition'];

            $competition = Competition::updateOrCreate(['id' => $c['id']], [
                'code' => $c['code'] ?? null,
                'name' => $c['name'],
                'type' => $c['type'] ?? null,
                'emblem' => $c['emblem'] ?? null,
                'season_start' => $payload['season']['startDate'] ?? null,
                'season_end' => $payload['season']['endDate'] ?? null,
                'imported_at' => now(),
            ]);

            $teams = collect($payload['teams'] ?? []);

            Team::upsert($teams->map(fn (array $t) => [
                'id' => $t['id'],
                'name' => $t['name'],
                'short_name' => $t['shortName'] ?? null,
                'tla' => $t['tla'] ?? null,
                'crest' => $t['crest'] ?? null,
                'venue' => $t['venue'] ?? null,
                'founded' => $t['founded'] ?? null,
                'club_colors' => $t['clubColors'] ?? null,
                'website' => $t['website'] ?? null,
                'area_name' => $t['area']['name'] ?? null,
                'coach_name' => $t['coach']['name'] ?? null,
            ])->all(), ['id']);

            $players = $teams->flatMap(fn (array $t) => collect($t['squad'] ?? [])->map(fn (array $p) => [
                'id' => $p['id'],
                'team_id' => $t['id'],
                'name' => $p['name'],
                'position' => $p['position'] ?? null,
                'shirt_number' => $p['shirtNumber'] ?? null,
                'date_of_birth' => $p['dateOfBirth'] ?? null,
                'nationality' => $p['nationality'] ?? null,
            ]))->all();

            foreach (array_chunk($players, 200) as $chunk) {
                Player::upsert($chunk, ['id']);
            }

            $competition->teams()->sync($teams->pluck('id')->all());

            return $competition;
        });
    }

    private function findLocal(string $idOrCode): ?Competition
    {
        return ctype_digit($idOrCode)
            ? Competition::find((int) $idOrCode)
            : Competition::where('code', strtoupper($idOrCode))->first();
    }

    private function withTeams(Competition $competition): Competition
    {
        return $competition->load(['teams' => fn ($q) => $q->withCount('players')->orderBy('name')])
            ->loadCount('teams');
    }

    private function ttl(): int
    {
        return (int) config('services.football_data.cache_minutes', 1440);
    }
}
