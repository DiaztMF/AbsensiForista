<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agenda extends Model
{
    protected $fillable = [
        'title',
        'location_name',
        'address',
        'latitude',
        'longitude',
        'radius_m',
        'weekday',
        'start_time',
        'end_time',
        'window_minutes',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_m' => 'integer',
            'weekday' => 'integer',
            'window_minutes' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AgendaSession::class);
    }
}