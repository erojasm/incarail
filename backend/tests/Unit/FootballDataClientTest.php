<?php

namespace Tests\Unit;

use App\Exceptions\FootballDataException;
use App\Services\FootballData\FootballDataClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FootballDataClientTest extends TestCase
{
    private function client(int $max = 2): FootballDataClient
    {
        return new FootballDataClient('https://api.football-data.org/v4', 'test-token', $max);
    }

    public function test_sends_token_in_header(): void
    {
        Http::fake(['*' => Http::response(['competitions' => [['id' => 1]]])]);

        $this->assertCount(1, $this->client()->competitions());
        Http::assertSent(fn ($request) => $request->hasHeader('X-Auth-Token', 'test-token'));
    }

    public function test_stops_calling_when_local_quota_is_used(): void
    {
        Http::fake(['*' => Http::response(['competitions' => []])]);
        $client = $this->client(2);

        $client->competitions();
        $client->competitions();

        try {
            $client->competitions();
            $this->fail('Se esperaba FootballDataException');
        } catch (FootballDataException $e) {
            $this->assertSame(429, $e->status);
            $this->assertGreaterThan(0, $e->retryAfter);
        }

        Http::assertSentCount(2);
    }

    public function test_upstream_429_blocks_next_calls(): void
    {
        Http::fake(['*' => Http::response([], 429, ['X-RequestCounter-Reset' => '30'])]);
        $client = $this->client(10);

        foreach (range(1, 2) as $ignored) {
            try {
                $client->competitions();
            } catch (FootballDataException $e) {
                $this->assertSame(429, $e->status);
            }
        }

        Http::assertSentCount(1);
    }

    public function test_connection_error_is_503(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->expectExceptionObject(FootballDataException::unavailable());
        $this->client()->competitions();
    }

    public function test_missing_token_is_reported(): void
    {
        $this->expectException(FootballDataException::class);
        (new FootballDataClient('https://api.football-data.org/v4', null, 10))->competitions();
    }
}
