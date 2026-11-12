<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Agenda\GenerateAgendaSessionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgendaRequest;
use App\Models\Agenda;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function index(): Response
    {
        $agendas = Agenda::with(['sessions' => function ($q) {
            $q->where('session_date', '>=', now()->toDateString())
                ->orderBy('session_date')
                ->limit(8);
        }])
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/Agendas/Index', [
            'agendas' => $agendas,
            'defaults' => AppSetting::current(),
        ]);
    }

    public function store(StoreAgendaRequest $request, GenerateAgendaSessionsAction $generator): RedirectResponse
    {
        $agenda = Agenda::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $generator->execute($agenda, weeksAhead: 4);

        Inertia::flash('success', 'Agenda mingguan dibuat dan sesi 4 minggu ke depan telah dijadwalkan.');

        return back();
    }

    public function update(
        StoreAgendaRequest $request,
        Agenda $agenda,
        GenerateAgendaSessionsAction $generator
    ): RedirectResponse {
        $agenda->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', $agenda->is_active),
        ]);

        // F3: Perubahan hanya memengaruhi sesi di masa depan
        $generator->syncFutureSessions($agenda, weeksAhead: 4);

        Inertia::flash('success', 'Agenda berhasil diperbarui untuk sesi mendatang.');

        return back();
    }
}
