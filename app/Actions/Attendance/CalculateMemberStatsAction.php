<?php

namespace App\Actions\Attendance;

use App\Models\AgendaSession;
use App\Models\User;
use Carbon\CarbonInterface;

class CalculateMemberStatsAction
{
    /**
     * Menghitung statistik kehadiran anggota sesuai rumus F5 PRD:
     * - Persentase = hadir / total sesi terlaksana (dibulatkan bilangan bulat)
     * - Skor = persentase / 10 (1 angka desimal, minimal 1.0)
     *
     * @return array{total_sessions: int, attended_count: int, percentage: int, score: float}
     */
    public function execute(
        User $user,
        ?int $agendaId = null,
        ?CarbonInterface $fromDate = null,
        ?CarbonInterface $toDate = null
    ): array {
        $sessionsQuery = AgendaSession::completed()
            ->when($agendaId, fn ($q) => $q->where('agenda_id', $agendaId))
            ->when($fromDate, fn ($q) => $q->whereDate('session_date', '>=', $fromDate->toDateString()))
            ->when($toDate, fn ($q) => $q->whereDate('session_date', '<=', $toDate->toDateString()));

        $sessionIds = $sessionsQuery->pluck('id');
        $totalSessions = $sessionIds->count();

        $attendedCount = $totalSessions > 0
            ? $user->attendances()->whereIn('agenda_session_id', $sessionIds)->count()
            : 0;

        $percentage = $totalSessions > 0
            ? (int) round(($attendedCount / $totalSessions) * 100)
            : 0;

        $score = max(1.0, round($percentage / 10, 1));

        return [
            'total_sessions' => $totalSessions,
            'attended_count' => $attendedCount,
            'percentage' => $percentage,
            'score' => $score,
        ];
    }
}