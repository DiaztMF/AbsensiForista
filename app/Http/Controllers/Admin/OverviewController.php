<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\AgendaSession;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __invoke(): Response
    {
        $now = now();
        $activeMembersCount = User::active()->count();

        // F10: Sesi hari ini atau sesi berikutnya yang terjadwal
        $focusSession = AgendaSession::with('agenda')
            ->where('status', SessionStatus::Scheduled)
            ->where('window_closes_at', '>=', $now->copy()->subHours(2))
            ->orderBy('starts_at')
            ->first();

        $focusPayload = null;
        if ($focusSession) {
            $presentCount = $focusSession->attendances()->count();

            $focusPayload = [
                'id' => $focusSession->id,
                'agenda_id' => $focusSession->agenda_id,
                'title' => $focusSession->agenda->title,
                'location_name' => $focusSession->agenda->location_name,
                'session_date' => $focusSession->session_date->toDateString(),
                'starts_at' => $focusSession->starts_at->toIso8601String(),
                'ends_at' => $focusSession->ends_at->toIso8601String(),
                'window_opens_at' => $focusSession->window_opens_at->toIso8601String(),
                'window_closes_at' => $focusSession->window_closes_at->toIso8601String(),
                'present_count' => $presentCount,
                'active_members_count' => $activeMembersCount,
            ];
        }

        return Inertia::render('Admin/Overview', [
            'serverNow' => $now->toIso8601String(),
            'focusSession' => $focusPayload,
            'activeMembersCount' => $activeMembersCount,
        ]);
    }
}
