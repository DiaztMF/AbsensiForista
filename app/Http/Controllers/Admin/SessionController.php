<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShiftSessionWindowRequest;
use App\Models\AgendaSession;
use App\Models\User;
use App\Notifications\AgendaReminderNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class SessionController extends Controller
{
    public function shiftWindow(ShiftSessionWindowRequest $request, AgendaSession $session): RedirectResponse
    {
        Gate::authorize('shiftWindow', $session);

        $newOpensAt = Carbon::parse($request->validated('window_opens_at'));
        $newClosesAt = Carbon::parse($request->validated('window_closes_at'));

        // F9: Reset flag reminder terkait agar pengingat window dihitung ulang sesuai jadwal baru
        $session->update([
            'window_opens_at' => $newOpensAt,
            'window_closes_at' => $newClosesAt,
            'reminded_window_open_at' => $newOpensAt->isFuture() ? null : $session->reminded_window_open_at,
            'reminded_before_close_at' => $newClosesAt->isFuture() ? null : $session->reminded_before_close_at,
        ]);

        return back()->with('success', 'Waktu check-in sesi berhasil digeser dan pengingat diperbarui.');
    }

    public function cancel(AgendaSession $session): RedirectResponse
    {
        Gate::authorize('cancel', $session);

        $session->update([
            'status' => SessionStatus::Cancelled,
        ]);

        return back()->with('success', 'Sesi pertemuan berhasil dibatalkan dan dikeluarkan dari perhitungan persentase.');
    }

    public function remindPending(AgendaSession $session): RedirectResponse
    {
        if ($session->status !== SessionStatus::Scheduled) {
            return back()->withErrors(['session' => 'Tidak dapat mengirim pengingat pada sesi yang dibatalkan.']);
        }

        $presentUserIds = $session->attendances()->pluck('user_id');

        $pendingMembers = User::active()
            ->whereNotIn('id', $presentUserIds)
            ->get();

        if ($pendingMembers->isNotEmpty()) {
            Notification::send(
                $pendingMembers,
                new AgendaReminderNotification(
                    session: $session,
                    title: 'Pengingat Presensi FORISTA',
                    body: "Segera lakukan presensi untuk {$session->agenda->title} sebelum pukul {$session->window_closes_at->format('H:i')} WIB."
                )
            );
        }

        return back()->with('success', "Pengingat dikirim ke {$pendingMembers->count()} anggota yang belum hadir.");
    }
}
