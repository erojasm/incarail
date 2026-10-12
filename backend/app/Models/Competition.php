<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// El id es el mismo de football-data
class Competition extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id', 'code', 'name', 'type', 'emblem', 'area_name', 'plan',
        'season_start', 'season_end', 'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'season_start' => 'date',
            'season_end'   => 'date',
            'imported_at'  => 'datetime',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }
}
