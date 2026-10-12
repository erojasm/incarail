<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id', 'team_id', 'name', 'position', 'shirt_number', 'date_of_birth', 'nationality',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'shirt_number' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where('name', 'like', "%{$term}%"));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['team_id'] ?? null, fn ($q, $teamId) => $q->where('team_id', $teamId))
            ->when($filters['position'] ?? null, fn ($q, $position) => $q->where('position', $position));
    }
}
