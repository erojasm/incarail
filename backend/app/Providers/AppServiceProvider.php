<?php

namespace App\Providers;

use App\Models\User;
use App\Services\FootballData\FootballDataClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public const TOKEN_HOURS = 8;

    public function register(): void
    {
        $this->app->singleton(FootballDataClient::class, fn () => new FootballDataClient(
            config('services.football_data.base_url'),
            config('services.football_data.token'),
            (int) config('services.football_data.max_per_minute', 9),
        ));
    }

    public function boot(): void
    {
        Passport::personalAccessTokensExpireIn(now()->addHours(self::TOKEN_HOURS));

        // Los permisos se resuelven contra la BD; "can:teams.read" funciona directo
        Gate::before(fn (User $user, string $ability) => $user->hasPermission($ability) ?: null);

        Gate::define('roles.list', fn (User $user) => $user->hasPermission('roles.manage') || $user->hasPermission('users.manage'));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip().'|'.$request->input('email')));
    }
}
