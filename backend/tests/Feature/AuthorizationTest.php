<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Support\Facades\Http;

class AuthorizationTest extends ApiTestCase
{
    public function test_guest_receives_401_in_json_format(): void
    {
        $this->getJson('/api/team')
            ->assertUnauthorized()
            ->assertJsonStructure(['message', 'code'])
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_read_only_user_can_list_teams(): void
    {
        $this->actingAsRole('consulta');
        Team::create(['id' => 1, 'name' => 'Equipo Local']);

        $this->getJson('/api/team')->assertOk()->assertJsonPath('data.0.name', 'Equipo Local');
    }

    public function test_read_only_user_cannot_import_competitions(): void
    {
        Http::fake();
        $this->actingAsRole('consulta');

        $this->getJson('/api/competitions/PL')->assertForbidden()->assertJsonPath('code', 'FORBIDDEN');
        Http::assertNothingSent();
    }

    public function test_read_only_user_cannot_manage_users(): void
    {
        $this->actingAsRole('consulta');

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [])->assertForbidden();
    }

    public function test_missing_team_returns_404(): void
    {
        $this->actingAsRole('consulta');

        $this->getJson('/api/team/999999')
            ->assertNotFound()
            ->assertJson(['code' => 'NOT_FOUND', 'message' => 'El equipo solicitado no existe.']);
    }

    public function test_admin_creates_user_with_hashed_password(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/api/users', ['name' => 'Nuevo', 'email' => 'nuevo@incarail.test', 'password' => 'secreto123', 'roles' => [1]])
            ->assertCreated()
            ->assertJsonPath('data.email', 'nuevo@incarail.test');

        $this->assertDatabaseMissing('users', ['password' => 'secreto123']);
    }

    public function test_invalid_data_returns_422(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/api/users', ['email' => 'no-es-correo'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['name', 'email', 'password']]);
    }

    public function test_me_returns_casl_rules(): void
    {
        $this->actingAsRole('consulta');

        $rules = $this->getJson('/api/me')->assertOk()->json('rules');

        $this->assertContains(['action' => 'read', 'subject' => 'Team'], $rules);
        $this->assertNotContains(['action' => 'import', 'subject' => 'Competition'], $rules);
    }
}
