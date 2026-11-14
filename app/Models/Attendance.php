<?php

namespace App\Models;

use App\Enums\AttendanceSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'agenda_session_id',
        'user_id',
        'status',
        'checked_at',
        'distance_m',
        'source',
        'manual_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'distance_m' => 'float',
            'source' => AttendanceSource::class,
        ];
    }

    /** @return BelongsTo<AgendaSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AgendaSession::class, 'agenda_session_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
