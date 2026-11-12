<?php

namespace App\Actions\Agenda;

use App\Enums\SessionStatus;
use App\Models\Agenda;
use App\Models\AgendaSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GenerateAgendaSessionsAction
{
    /**
     * Membuat sesi mingguan hingga $weeksAhead minggu ke depan secara idempoten.
     */
    public function execute(Agenda $agenda, int $weeksAhead = 4): int
    {
        if (! $agenda->is_active) {
            return 0;
        }

        $today = now()->startOfDay();
        $rangeStart = $agenda->starts_on->gt($today) ? $agenda->starts_on->copy() : $today;
        $rangeEnd = $today->copy()->addWeeks($weeksAhead);

        if ($agenda->ends_on && $agenda->ends_on->lt($rangeEnd)) {
            $rangeEnd = $agenda->ends_on->copy();
        }

        if ($rangeStart->gt($rangeEnd)) {
            return 0;
        }

        $createdCount = 0;

        foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $date) {
            if ($date->dayOfWeek !== $agenda->weekday) {
                continue;
            }

            [$startsAt, $endsAt, $windowOpensAt, $windowClosesAt] = $this->buildTimestamps($agenda, $date);

            $session = AgendaSession::firstOrCreate(
                [
                    'agenda_id' => $agenda->id,
                    'session_date' => $date->toDateString(),
                ],
                [
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'window_opens_at' => $windowOpensAt,
                    'window_closes_at' => $windowClosesAt,
                    'status' => SessionStatus::Scheduled,
                ]
            );

            if ($session->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return $createdCount;
    }

    /**
     * Saat Admin mengubah agenda, perbarui hanya sesi di masa depan (sesuai F3 PRD)
     * dan generate sesi baru jika rentang tanggal diperpanjang.
     */
    public function syncFutureSessions(Agenda $agenda, int $weeksAhead = 4): void
    {
        $now = now();

        $futureSessions = $agenda->sessions()
            ->where('status', SessionStatus::Scheduled)
            ->where('starts_at', '>', $now)
            ->get();

        foreach ($futureSessions as $session) {
            if ($agenda->ends_on && $session->session_date->gt($agenda->ends_on)) {
                $session->delete();
                continue;
            }

            [$startsAt, $endsAt, $windowOpensAt, $windowClosesAt] = $this->buildTimestamps(
                $agenda,
                $session->session_date
            );

            $session->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'window_opens_at' => $windowOpensAt,
                'window_closes_at' => $windowClosesAt,
            ]);
        }

        $this->execute($agenda, $weeksAhead);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon}
     */
    private function buildTimestamps(Agenda $agenda, Carbon $date): array
    {
        $dateString = $date->toDateString();
        $startsAt = Carbon::parse("{$dateString} {$agenda->start_time}");
        $endsAt = Carbon::parse("{$dateString} {$agenda->end_time}");

        if ($endsAt->lte($startsAt)) {
            $endsAt->addDay();
        }

        $windowOpensAt = $endsAt->copy();
        $windowClosesAt = $endsAt->copy()->addMinutes($agenda->window_minutes);

        return [$startsAt, $endsAt, $windowOpensAt, $windowClosesAt];
    }
}