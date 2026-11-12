<?php

namespace App\Exports;

use App\Actions\Attendance\CalculateMemberStatsAction;
use App\Actions\Attendance\ProcessCheckInAction;
use App\Enums\AttemptResult;
use App\Enums\AttendanceSource;
use App\Models\Agenda;
use App\Models\AgendaSession;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\Division;
use App\Models\User;
use Carbon\Carbon;

class RecapDataBuilder
{
    public function __construct(
        private CalculateMemberStatsAction $statsAction
    ) {}

    /**
     * Membangun data rekap per sesi pertemuan (Hadir / Belum hadir).
     */
    public function buildSessionRecap(?AgendaSession $session, ?int $divisionId = null): array
    {
        $divisionName = $divisionId
            ? (Division::find($divisionId)?->name ?? 'Semua Divisi')
            : 'Semua Divisi';

        if (! $session) {
            return [
                'meta' => [
                    'agenda_title' => '-',
                    'location_name' => '-',
                    'session_date' => '-',
                    'window_time' => '-',
                    'division_name' => $divisionName,
                ],
                'rows' => [],
                'summary' => [
                    'total_active' => 0,
                    'present_count' => 0,
                    'absent_count' => 0,
                    'auto_count' => 0,
                    'manual_count' => 0,
                ],
            ];
        }

        $members = User::with('division')
            ->active()
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->orderBy('name')
            ->get();

        $attendances = Attendance::with('creator')
            ->where('agenda_session_id', $session->id)
            ->get()
            ->keyBy('user_id');

        $attemptsByUser = AttendanceAttempt::where('agenda_session_id', $session->id)
            ->where('result', AttemptResult::OutsideRadius)
            ->get()
            ->groupBy('user_id');

        $presentCount = 0;
        $autoCount = 0;
        $manualCount = 0;

        $rows = $members->map(function (User $member) use (
            $attendances,
            $attemptsByUser,
            &$presentCount,
            &$autoCount,
            &$manualCount
        ) {
            $att = $attendances->get($member->id);
            $failedCount = $attemptsByUser->get($member->id)?->count() ?? 0;

            if ($att) {
                $presentCount++;
                if ($att->source === AttendanceSource::Auto) {
                    $autoCount++;
                } else {
                    $manualCount++;
                }
            }

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'username' => $member->username,
                'division' => $member->division->name,
                'status' => $att ? 'hadir' : 'belum_hadir',
                'status_label' => $att ? 'Hadir' : 'Belum hadir',
                'checked_at' => $att?->checked_at?->format('H:i:s') ?? '-',
                'distance_m' => $att?->distance_m,
                'distance_label' => $att?->distance_m !== null
                    ? number_format($att->distance_m, 1, ',', '.').' m'
                    : '-',
                'source' => $att?->source?->value,
                'source_label' => match ($att?->source) {
                    AttendanceSource::Auto => 'Otomatis (GPS)',
                    AttendanceSource::Manual => 'Manual',
                    default => '-',
                },
                'manual_reason' => $att?->manual_reason,
                'created_by_name' => $att?->creator?->name,
                'failed_attempts' => $failedCount,
                'is_locked' => ! $att && $failedCount >= ProcessCheckInAction::MAX_OUTSIDE_ATTEMPTS,
            ];
        })->values()->all();

        $totalActive = count($rows);

        return [
            'meta' => [
                'agenda_title' => $session->agenda->title,
                'location_name' => $session->agenda->location_name,
                'session_date' => $session->session_date->translatedFormat('l, d F Y'),
                'window_time' => $session->window_opens_at->format('H:i').' - '.$session->window_closes_at->format('H:i').' WIB',
                'division_name' => $divisionName,
            ],
            'rows' => $rows,
            'summary' => [
                'total_active' => $totalActive,
                'present_count' => $presentCount,
                'absent_count' => $totalActive - $presentCount,
                'auto_count' => $autoCount,
                'manual_count' => $manualCount,
            ],
        ];
    }

    /**
     * Membangun data rekap kumulatif rentang tanggal (persentase & skor 1.0 - 10.0).
     */
    public function buildRangeRecap(
        ?int $agendaId = null,
        ?int $divisionId = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $agendaTitle = $agendaId
            ? (Agenda::find($agendaId)?->title ?? 'Semua Agenda')
            : 'Semua Agenda';

        $divisionName = $divisionId
            ? (Division::find($divisionId)?->name ?? 'Semua Divisi')
            : 'Semua Divisi';

        $fromCarbon = $fromDate ? Carbon::parse($fromDate) : null;
        $toCarbon = $toDate ? Carbon::parse($toDate) : null;

        $members = User::with('division')
            ->active()
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->orderBy('name')
            ->get();

        $rows = $members->map(function (User $member) use ($agendaId, $fromCarbon, $toCarbon) {
            $stats = $this->statsAction->execute($member, $agendaId, $fromCarbon, $toCarbon);

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'username' => $member->username,
                'division' => $member->division->name,
                'total_sessions' => $stats['total_sessions'],
                'attended_count' => $stats['attended_count'],
                'percentage' => $stats['percentage'],
                'score' => $stats['score'],
            ];
        })->values()->all();

        $memberCount = count($rows);
        $avgPercentage = $memberCount > 0
            ? (int) round(collect($rows)->avg('percentage'))
            : 0;
        $avgScore = $memberCount > 0
            ? round(collect($rows)->avg('score'), 1)
            : 1.0;

        return [
            'meta' => [
                'agenda_title' => $agendaTitle,
                'division_name' => $divisionName,
                'period_label' => ($fromCarbon ? $fromCarbon->format('d/m/Y') : 'Awal')
                    .' s.d. '
                    .($toCarbon ? $toCarbon->format('d/m/Y') : 'Sekarang'),
            ],
            'rows' => $rows,
            'summary' => [
                'total_active' => $memberCount,
                'total_sessions' => $rows[0]['total_sessions'] ?? 0,
                'average_percentage' => $avgPercentage,
                'average_score' => $avgScore,
            ],
        ];
    }
}
