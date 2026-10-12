<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'short_name', 'tla', 'crest', 'venue', 'founded',
        'club_colors', 'website', 'area_name', 'coach_name',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class)->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn($q) => $q->where(fn($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('short_name', 'like', "%{$term}%")
            ->orWhere('tla', 'like', "%{$term}%")));
    }

    public function scopeInCompetition(Builder $query, ?int $competitionId): Builder
    {
        return $query->when($competitionId, fn($q) => $q->whereHas(
            'competitions', fn($q) => $q->whereKey($competitionId)
        ));
    }
}
