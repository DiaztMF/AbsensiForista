<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceSource;
use App\Enums\AuditAction;
use App\Enums\SessionStatus;
use App\Models\AgendaSession;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordManualAttendanceAction
{
    public function execute(User $admin, AgendaSession $session, User $member, string $reason): Attendance
    {
        $trimmedReason = trim($reason);

        if (mb_strlen($trimmedReason) < 10) {
            throw new InvalidArgumentException('Alasan presensi manual wajib diisi minimal 10 karakter.');
        }

        if ($session->status === SessionStatus::Cancelled) {
            throw new InvalidArgumentException('Tidak dapat mencatat presensi pada sesi yang dibatalkan.');
        }

        $alreadyPresent = Attendance::where('agenda_session_id', $session->id)
            ->where('user_id', $member->id)
            ->exists();

        if ($alreadyPresent) {
            throw new InvalidArgumentException('Anggota ini sudah tercatat hadir pada sesi tersebut.');
        }

        return DB::transaction(function () use ($admin, $session, $member, $trimmedReason) {
            $attendance = Attendance::create([
                'agenda_session_id' => $session->id,
                'user_id' => $member->id,
                'status' => 'hadir',
                'checked_at' => now(),
                'distance_m' => null,
                'source' => AttendanceSource::Manual,
                'manual_reason' => $trimmedReason,
                'created_by' => $admin->id,
            ]);

            AuditLog::create([
                'actor_id' => $admin->id,
                'target_user_id' => $member->id,
                'action' => AuditAction::ManualAttendance,
                'reason' => $trimmedReason,
                'metadata' => [
                    'agenda_id' => $session->agenda_id,
                    'agenda_title' => $session->agenda->title,
                    'agenda_session_id' => $session->id,
                    'session_date' => $session->session_date->toDateString(),
                ],
            ]);

            return $attendance;
        });
    }
}