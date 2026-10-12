<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\Http;

class CompetitionImportTest extends ApiTestCase
{
    public function test_import_saves_teams_and_players_without_duplicates(): void
    {
        config(['services.football_data.cache_minutes' => 0]);
        Http::fake(['*/competitions/PL/teams' => Http::response($this->leaguePayload())]);
        $this->actingAsRole('operador');

        $this->getJson('/api/competitions/PL')->assertOk()->assertJsonCount(2, 'data.teams');
        $this->getJson('/api/competitions/PL')->assertOk();

        Http::assertSentCount(2);
        $this->assertSame(2, Team::count());
        $this->assertSame(2, Player::count());
        $this->assertDatabaseHas('players', ['name' => 'Jugador Dos', 'position' => 'Defence', 'shirt_number' => 4]);
    }

    public function test_recent_import_is_served_from_database(): void
    {
        Http::fake(['*/competitions/PL/teams' => Http::response($this->leaguePayload())]);
        $this->actingAsRole('operador');

        $this->getJson('/api/competitions/PL')->assertJsonPath('meta.source', 'football-data');
        $this->getJson('/api/competitions/PL')->assertJsonPath('meta.source', 'database');

        Http::assertSentCount(1);
    }

    public function test_saved_data_is_served_when_football_data_is_down(): void
    {
        config(['services.football_data.cache_minutes' => 0]);
        Http::fakeSequence()
            ->push($this->leaguePayload())
            ->push(['message' => 'error'], 500);
        $this->actingAsRole('operador');

        $this->getJson('/api/competitions/PL')->assertOk();
        $this->getJson('/api/competitions/PL')
            ->assertOk()
            ->assertJsonPath('meta.source', 'database')
            ->assertJsonCount(2, 'data.teams');
    }

    public function test_upstream_failure_without_local_data_returns_502(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $this->actingAsRole('operador');

        $this->getJson('/api/competitions/PL')->assertStatus(502)->assertJsonPath('code', 'UPSTREAM_ERROR');
    }

    public function test_local_quota_returns_429_with_retry_after(): void
    {
        config(['services.football_data.max_per_minute' => 1, 'services.football_data.cache_minutes' => 0]);
        Http::fake(['*' => Http::response([], 404)]);
        $this->actingAsRole('operador');

        $this->getJson('/api/competitions/AAA')->assertNotFound();
        $this->getJson('/api/competitions/BBB')
            ->assertStatus(429)
            ->assertJsonPath('code', 'UPSTREAM_RATE_LIMIT')
            ->assertHeader('Retry-After');

        Http::assertSentCount(1);
    }

    public function test_teams_and_players_do_not_call_football_data(): void
    {
        Http::fake();
        $this->actingAsRole('consulta');

        $this->getJson('/api/team')->assertOk();
        $this->getJson('/api/players')->assertOk();

        Http::assertNothingSent();
    }
}
