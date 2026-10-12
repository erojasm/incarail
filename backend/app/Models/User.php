<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_active' => true];

    private ?Collection $cachedPermissions = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function permissions(): Collection
    {
        return $this->cachedPermissions ??= $this->roles()->with('permissions')->get()
            ->flatMap->permissions
            ->unique('id')
            ->values();
    }

    public function hasPermission(string $name): bool
    {
        return $this->is_active && $this->permissions()->contains('name', $name);
    }

    // Reglas en formato CASL: [{ action, subject }]
    public function abilityRules(): array
    {
        return $this->permissions()
            ->map(fn (Permission $p) => ['action' => $p->action, 'subject' => $p->subject])
            ->all();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")));
    }
}
