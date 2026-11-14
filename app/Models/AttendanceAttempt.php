<?php

namespace App\Models;

use App\Enums\AttemptResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAttempt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'agenda_session_id',
        'user_id',
        'distance_m',
        'accuracy_m',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'distance_m' => 'float',
            'accuracy_m' => 'float',
            'result' => AttemptResult::class,
            'created_at' => 'datetime',
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
}
