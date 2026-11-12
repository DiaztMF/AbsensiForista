<?php

namespace App\Http\Controllers\Member;

use App\Actions\Attendance\CalculateMemberStatsAction;
use App\Actions\Attendance\ProcessCheckInAction;
use App\Enums\AttemptResult;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\AgendaSession;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CalculateMemberStatsAction $statsAction): Response
    {
        $user = $request->user();
        $now = now();

        // Cari sesi hari ini atau sesi terjadwal terdekat berikutnya
        $activeSession = AgendaSession::with('agenda')
            ->where('status', SessionStatus::Scheduled)
            ->where('window_closes_at', '>=', $now->copy()->subHours(2))
            ->orderBy('starts_at')
            ->first();

        $sessionPayload = null;
        if ($activeSession) {
            $attendance = Attendance::where('agenda_session_id', $activeSession->id)
                ->where('user_id', $user->id)
                ->first();

            $failedAttempts = AttendanceAttempt::where('agenda_session_id', $activeSession->id)
                ->where('user_id', $user->id)
                ->where('result', AttemptResult::OutsideRadius)
                ->count();

            $sessionPayload = [
                'id' => $activeSession->id,
                'title' => $activeSession->agenda->title,
                'location_name' => $activeSession->agenda->location_name,
                'address' => $activeSession->agenda->address,
                'latitude' => $activeSession->agenda->latitude,
                'longitude' => $activeSession->agenda->longitude,
                'radius_m' => $activeSession->agenda->radius_m,
                'session_date' => $activeSession->session_date->toDateString(),
                'starts_at' => $activeSession->starts_at->toIso8601String(),
                'ends_at' => $activeSession->ends_at->toIso8601String(),
                'window_opens_at' => $activeSession->window_opens_at->toIso8601String(),
                'window_closes_at' => $activeSession->window_closes_at->toIso8601String(),
                'has_checked_in' => $attendance !== null,
                'checked_at' => $attendance?->checked_at?->toIso8601String(),
                'distance_m' => $attendance?->distance_m,
                'source' => $attendance?->source?->value,
                'failed_attempts' => $failedAttempts,
                'remaining_attempts' => max(0, ProcessCheckInAction::MAX_OUTSIDE_ATTEMPTS - $failedAttempts),
                'is_locked' => $failedAttempts >= ProcessCheckInAction::MAX_OUTSIDE_ATTEMPTS,
            ];
        }

        // Riwayat kehadiran anggota dikelompokkan per bulan (hanya milik sendiri sesuai F5)
        $history = Attendance::with(['session.agenda'])
            ->where('user_id', $user->id)
            ->orderByDesc('checked_at')
            ->get()
            ->map(fn (Attendance $item) => [
                'id' => $item->id,
                'agenda_title' => $item->session->agenda->title,
                'location_name' => $item->session->agenda->location_name,
                'session_date' => $item->session->session_date->toDateString(),
                'month_key' => $item->session->session_date->format('Y-m'),
                'month_label' => $item->session->session_date->translatedFormat('F Y'),
                'checked_at' => $item->checked_at->format('H:i'),
                'distance_m' => $item->distance_m,
                'source' => $item->source->value,
            ]);

        return Inertia::render('Member/Dashboard', [
            'serverNow' => $now->toIso8601String(),
            'activeSession' => $sessionPayload,
            'stats' => $statsAction->execute($user),
            'history' => $history,
            'unreadNotificationsCount' => $user->unreadNotifications()->count(),
            'vapidPublicKey' => config('services.webpush.public_key'),
        ]);
    }
}
