<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaSession extends Model
{
    protected $fillable = [
        'agenda_id',
        'session_date',
        'starts_at',
        'ends_at',
        'window_opens_at',
        'window_closes_at',
        'status',
        'reminded_before_start_at',
        'reminded_window_open_at',
        'reminded_before_close_at',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'window_opens_at' => 'datetime',
            'window_closes_at' => 'datetime',
            'status' => SessionStatus::class,
            'reminded_before_start_at' => 'datetime',
            'reminded_window_open_at' => 'datetime',
            'reminded_before_close_at' => 'datetime',
        ];
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::Scheduled);
    }

    /**
     * Sesi yang sudah selesai (window sudah ditutup) dan tidak dibatalkan.
     * Dipakai sebagai pembagi (denominator) persentase dan skor anggota.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->scheduled()->where('window_closes_at', '<', now());
    }

    public function isWindowOpen(): bool
    {
        return $this->status === SessionStatus::Scheduled
            && now()->betweenIncluded($this->window_opens_at, $this->window_closes_at);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AttendanceAttempt::class);
    }
}