<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\RecordManualAttendanceAction;
use App\Exports\RecapDataBuilder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualAttendanceRequest;
use App\Models\Agenda;
use App\Models\AgendaSession;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class RecapController extends Controller
{
    public function index(Request $request, RecapDataBuilder $builder): Response
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

        $activeSession = null;
        if ($selectedSessionId) {
            $activeSession = AgendaSession::with('agenda')->whereKey($selectedSessionId)->first();
        } elseif ($sessions->first() !== null) {
            $activeSession = AgendaSession::with('agenda')->whereKey($sessions->first()->id)->first();
        }

        // Single source of truth: rekap data dibangun via RecapDataBuilder
        $sessionRows = $activeSession ? $builder->buildSessionRecap($activeSession, $divisionId)['rows'] : [];

        $rangeData = $builder->buildRangeRecap($selectedAgendaId, $divisionId, $fromDate, $toDate);
        $rangeRows = $rangeData['rows'];

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
        $member = User::whereKey($request->validated('user_id'))->firstOrFail();

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

        Inertia::flash('success', "Presensi manual untuk {$member->name} berhasil dicatat.");

        return back();
    }
}
