<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\CalculateMemberStatsAction;
use App\Actions\Attendance\ProcessCheckInAction;
use App\Actions\Attendance\RecordManualAttendanceAction;
use App\Enums\AttemptResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualAttendanceRequest;
use App\Models\Agenda;
use App\Models\AgendaSession;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\Division;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class RecapController extends Controller
{
    public function index(Request $request, CalculateMemberStatsAction $statsAction): Response
    {
        $agendas = Agenda::orderBy('title')->get(['id', 'title']);
        $divisions = Division::orderBy('name')->get(['id', 'name']);

        $selectedAgendaId = $request->integer('agenda_id') ?: ($agendas->first()?->id);
        $selectedSessionId = $request->integer('session_id');
        $divisionId = $request->integer('division_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $sessions = $selectedAgendaId
            ? AgendaSession::where('agenda_id', $selectedAgendaId)
                ->orderByDesc('session_date')
                ->limit(30)
                ->get(['id', 'session_date', 'starts_at', 'ends_at', 'window_opens_at', 'window_closes_at', 'status'])
            : collect();

        $activeSession = $selectedSessionId
            ? AgendaSession::with('agenda')->find($selectedSessionId)
            : ($sessions->first() ? AgendaSession::with('agenda')->find($sessions->first()->id) : null);

        $activeMembers = User::with('division')
            ->active()
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->orderBy('name')
            ->get();

        // Data rekap per sesi
        $sessionRows = [];
        if ($activeSession) {
            $attendances = Attendance::with('creator')
                ->where('agenda_session_id', $activeSession->id)
                ->get()
                ->keyBy('user_id');

            $attemptsByUser = AttendanceAttempt::where('agenda_session_id', $activeSession->id)
                ->where('result', AttemptResult::OutsideRadius)
                ->get()
                ->groupBy('user_id');

            $sessionRows = $activeMembers->map(function (User $member) use ($attendances, $attemptsByUser) {
                $att = $attendances->get($member->id);
                $failedCount = $attemptsByUser->get($member->id)?->count() ?? 0;

                return [
                    'user_id' => $member->id,
                    'name' => $member->name,
                    'username' => $member->username,
                    'division' => $member->division->name,
                    'status' => $att ? 'hadir' : 'belum_hadir',
                    'checked_at' => $att?->checked_at?->format('H:i:s'),
                    'distance_m' => $att?->distance_m,
                    'source' => $att?->source?->value,
                    'manual_reason' => $att?->manual_reason,
                    'created_by_name' => $att?->creator?->name,
                    'failed_attempts' => $failedCount,
                    'is_locked' => ! $att && $failedCount >= ProcessCheckInAction::MAX_OUTSIDE_ATTEMPTS,
                ];
            })->values();
        }

        // Data rekap rentang tanggal (persentase & skor 1 - 10)
        $fromCarbon = $fromDate ? Carbon::parse($fromDate) : null;
        $toCarbon = $toDate ? Carbon::parse($toDate) : null;

        $rangeRows = $activeMembers->map(function (User $member) use ($statsAction, $selectedAgendaId, $fromCarbon, $toCarbon) {
            $stats = $statsAction->execute($member, $selectedAgendaId, $fromCarbon, $toCarbon);

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'division' => $member->division->name,
                ...$stats,
            ];
        })->values();

        return Inertia::render('Admin/Recaps/Index', [
            'agendas' => $agendas,
            'divisions' => $divisions,
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'sessionRows' => $sessionRows,
            'rangeRows' => $rangeRows,
            'filters' => [
                'agenda_id' => $selectedAgendaId,
                'session_id' => $activeSession?->id,
                'division_id' => $divisionId ?: null,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }

    public function storeManual(
        StoreManualAttendanceRequest $request,
        AgendaSession $session,
        RecordManualAttendanceAction $manualAction
    ): RedirectResponse {
        $member = User::findOrFail($request->validated('user_id'));

        try {
            $manualAction->execute(
                admin: $request->user(),
                session: $session,
                member: $member,
                reason: $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['manual' => $e->getMessage()]);
        }

        return back()->with('success', "Presensi manual untuk {$member->name} berhasil dicatat.");
    }
}
