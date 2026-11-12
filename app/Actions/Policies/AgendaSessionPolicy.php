<?php

namespace App\Policies;

use App\Enums\SessionStatus;
use App\Models\AgendaSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AgendaSessionPolicy
{
    /**
     * F10: Sesi tidak dapat dibatalkan jika sudah ada data kehadiran.
     */
    public function cancel(User $user, AgendaSession $session): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Hanya Admin yang dapat membatalkan sesi.');
        }

        if ($session->status === SessionStatus::Cancelled) {
            return Response::deny('Sesi ini sudah dibatalkan sebelumnya.');
        }

        if ($session->attendances()->exists()) {
            return Response::deny('Sesi tidak dapat dibatalkan karena sudah terdapat data presensi anggota.');
        }

        return Response::allow();
    }

    /**
     * F9: Admin dapat menggeser check-in window untuk satu sesi tertentu jika rapat molor.
     */
    public function shiftWindow(User $user, AgendaSession $session): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Hanya Admin yang dapat mengubah jadwal sesi.');
        }

        if ($session->status === SessionStatus::Cancelled) {
            return Response::deny('Sesi yang dibatalkan tidak dapat diubah.');
        }

        return Response::allow();
    }
}
