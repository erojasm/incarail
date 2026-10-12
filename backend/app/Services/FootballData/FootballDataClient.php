<?php

namespace App\Services\FootballData;

use App\Exceptions\FootballDataException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

// Única clase que habla con football-data.org
class FootballDataClient
{
    public const LIMITER_KEY = 'football-data:requests';
    public const BLOCK_KEY = 'football-data:blocked-until';

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $token,
        private readonly int $maxPerMinute,
    ) {}

    public function competitions(): array
    {
        return $this->get('competitions')['competitions'] ?? [];
    }

    // Trae la liga con sus equipos y planteles en una sola llamada
    public function competitionTeams(string $idOrCode): array
    {
        return $this->get("competitions/{$idOrCode}/teams");
    }

    public function remainingRequests(): int
    {
        return RateLimiter::remaining(self::LIMITER_KEY, $this->maxPerMinute);
    }

    private function get(string $path): array
    {
        $this->reserveQuota();

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['X-Auth-Token' => $this->token])
                ->acceptJson()
                ->timeout(15)
                ->get($path);
        } catch (ConnectionException) {
            throw FootballDataException::unavailable();
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        throw match (true) {
            $response->status() === 429 => $this->blockFor((int) $response->header('X-RequestCounter-Reset') ?: 60),
            $response->status() === 403 => FootballDataException::restricted(),
            in_array($response->status(), [400, 404]) => FootballDataException::notFound(),
            default => FootballDataException::badResponse($response->status()),
        };
    }

    // Cuenta la petición antes de salir; si no hay cupo ni siquiera llamamos
    private function reserveQuota(): void
    {
        if (blank($this->token)) {
            throw FootballDataException::unavailable('Falta configurar FOOTBALL_DATA_TOKEN en el .env.');
        }

        $blockedUntil = (int) Cache::get(self::BLOCK_KEY, 0);
        if ($blockedUntil > now()->timestamp) {
            throw FootballDataException::rateLimited($blockedUntil - now()->timestamp);
        }

        if (! RateLimiter::attempt(self::LIMITER_KEY, $this->maxPerMinute, fn () => true, 60)) {
            throw FootballDataException::rateLimited(RateLimiter::availableIn(self::LIMITER_KEY));
        }
    }

    // Si football-data igual nos corta, respetamos el tiempo que indica
    private function blockFor(int $seconds): FootballDataException
    {
        Cache::put(self::BLOCK_KEY, now()->timestamp + $seconds, $seconds);

        return FootballDataException::rateLimited($seconds);
    }
}
