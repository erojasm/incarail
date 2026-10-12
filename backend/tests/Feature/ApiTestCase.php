<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.football_data.token' => 'test-token',
            'services.football_data.base_url' => 'https://api.football-data.org/v4',
        ]);
    }

    protected function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $role)->value('id'));

        Passport::actingAs($user, [], 'api');

        return $user;
    }

    protected function leaguePayload(): array
    {
        return [
            'competition' => ['id' => 2021, 'code' => 'PL', 'name' => 'Premier League', 'type' => 'LEAGUE', 'emblem' => null],
            'season' => ['startDate' => '2026-08-15', 'endDate' => '2027-05-23'],
            'teams' => [
                ['id' => 57, 'name' => 'Arsenal FC', 'shortName' => 'Arsenal', 'tla' => 'ARS', 'area' => ['name' => 'England'],
                    'squad' => [['id' => 1, 'name' => 'Jugador Uno', 'position' => 'Goalkeeper', 'shirtNumber' => 1]]],
                ['id' => 61, 'name' => 'Chelsea FC', 'shortName' => 'Chelsea', 'tla' => 'CHE', 'area' => ['name' => 'England'],
                    'squad' => [['id' => 2, 'name' => 'Jugador Dos', 'position' => 'Defence', 'shirtNumber' => 4]]],
            ],
        ];
    }
}
