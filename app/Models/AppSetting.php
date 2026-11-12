<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'default_radius_m',
        'default_window_minutes',
        'gps_accuracy_ceiling_m',
        'remind_before_start_minutes',
        'remind_on_window_open',
        'remind_before_close_minutes',
    ];

    protected function casts(): array
    {
        return [
            'default_radius_m' => 'integer',
            'default_window_minutes' => 'integer',
            'gps_accuracy_ceiling_m' => 'integer',
            'remind_before_start_minutes' => 'integer',
            'remind_on_window_open' => 'boolean',
            'remind_before_close_minutes' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'default_radius_m' => 20,
            'default_window_minutes' => 15,
            'gps_accuracy_ceiling_m' => 20,
            'remind_before_start_minutes' => 30,
            'remind_on_window_open' => true,
            'remind_before_close_minutes' => 5,
        ]);
    }
}
