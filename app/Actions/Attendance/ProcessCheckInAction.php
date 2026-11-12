<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceSource;
use App\Enums\AttemptResult;
use App\Enums\SessionStatus;
use App\Models\AgendaSession;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ProcessCheckInAction
{
    public const MAX_OUTSIDE_ATTEMPTS = 3;

    /**
     * Koordinat mentah ($userLat, $userLng) hanya dipakai di memori untuk
     * menghitung jarak Haversine dan TIDAK PERNAH disimpan ke database.
     */
    public function execute(
        User $user,
        AgendaSession $session,
        float $userLat,
        float $userLng,
        float $accuracyM
    ): CheckInResult {
        $now = now(); // Selalu gunakan waktu server (Asia/Jakarta)

        if ($session->status !== SessionStatus::Scheduled) {
            return CheckInResult::rejected('session_cancelled', 'Sesi pertemuan ini telah dibatalkan.');
        }

        if ($now->lt($session->window_opens_at)) {
            return CheckInResult::rejected(
                'window_not_open',
                'Presensi belum dibuka. Silakan tunggu hingga ' . $session->window_opens_at->format('H:i') . ' WIB.'
            );
        }

        if ($now->gt($session->window_closes_at)) {
            return CheckInResult::rejected(
                'window_closed',
                'Waktu presensi telah ditutup pada ' . $session->window_closes_at->format('H:i') . ' WIB.'
            );
        }

        // Cek apakah anggota sudah tercatat hadir (idempoten, mencegah duplikat)
        $existingAttendance = Attendance::where('agenda_session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingAttendance) {
            return CheckInResult::success($existingAttendance, 'Presensi Anda sudah tercatat sebelumnya.');
        }

        // Hitung jumlah kegagalan akibat di luar radius (maksimal 3x)
        $failedAttempts = AttendanceAttempt::where('agenda_session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('result', AttemptResult::OutsideRadius)
            ->count();

        if ($failedAttempts >= self::MAX_OUTSIDE_ATTEMPTS) {
            return CheckInResult::rejected(
                'attempts_locked',
                'Batas 3 kali percobaan di luar radius telah habis. Silakan hubungi Admin FORISTA untuk presensi manual.',
                remainingAttempts: 0
            );
        }

        // Validasi batas akurasi GPS (default <= 20m). Jika buruk, tidak mengurangi kuota 3x.
        $accuracyCeiling = AppSetting::current()->gps_accuracy_ceiling_m;
        if ($accuracyM > $accuracyCeiling) {
            AttendanceAttempt::create([
                'agenda_session_id' => $session->id,
                'user_id' => $user->id,
                'distance_m' => null,
                'accuracy_m' => round($accuracyM, 2),
                'result' => AttemptResult::PoorAccuracy,
            ]);

            $remaining = self::MAX_OUTSIDE_ATTEMPTS - $failedAttempts;

            return CheckInResult::rejected(
                'poor_accuracy',
                sprintf(
                    'Akurasi GPS terlalu rendah (±%dm, maksimal %dm). Pindah ke area terbuka dan coba lagi. Kuota percobaan tidak berkurang.',
                    (int) round($accuracyM),
                    $accuracyCeiling
                ),
                remainingAttempts: $remaining
            );
        }

        $agenda = $session->agenda;
        $distanceM = $this->calculateDistanceMeters(
            $agenda->latitude,
            $agenda->longitude,
            $userLat,
            $userLng
        );

        // Jika berada di dalam radius yang diizinkan
        if ($distanceM <= $agenda->radius_m) {
            try {
                $attendance = DB::transaction(function () use ($session, $user, $distanceM, $accuracyM, $now) {
                    AttendanceAttempt::create([
                        'agenda_session_id' => $session->id,
                        'user_id' => $user->id,
                        'distance_m' => $distanceM,
                        'accuracy_m' => round($accuracyM, 2),
                        'result' => AttemptResult::Matched,
                    ]);

                    return Attendance::create([
                        'agenda_session_id' => $session->id,
                        'user_id' => $user->id,
                        'status' => 'hadir',
                        'checked_at' => $now,
                        'distance_m' => $distanceM,
                        'source' => AttendanceSource::Auto,
                    ]);
                });

                return CheckInResult::success($attendance);
            } catch (UniqueConstraintViolationException) {
                // Menangani race condition jika 2 request identik masuk bersamaan
                $attendance = Attendance::where('agenda_session_id', $session->id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                return CheckInResult::success($attendance, 'Presensi Anda sudah tercatat sebelumnya.');
            }
        }

        // Jika di luar radius: catat attempt dan kurangi sisa kuota
        AttendanceAttempt::create([
            'agenda_session_id' => $session->id,
            'user_id' => $user->id,
            'distance_m' => $distanceM,
            'accuracy_m' => round($accuracyM, 2),
            'result' => AttemptResult::OutsideRadius,
        ]);

        $newFailedCount = $failedAttempts + 1;
        $remaining = max(0, self::MAX_OUTSIDE_ATTEMPTS - $newFailedCount);

        if ($remaining === 0) {
            return CheckInResult::rejected(
                'attempts_locked',
                sprintf(
                    'Jarak Anda %sm dari lokasi (maksimal %dm). Batas 3 kali percobaan habis, silakan hubungi Admin FORISTA.',
                    number_format($distanceM, 1, ',', '.'),
                    $agenda->radius_m
                ),
                distanceM: $distanceM,
                remainingAttempts: 0
            );
        }

        return CheckInResult::rejected(
            'outside_radius',
            sprintf(
                'Anda berada %sm dari lokasi rapat (batas radius %dm). Sisa percobaan: %d kali.',
                number_format($distanceM, 1, ',', '.'),
                $agenda->radius_m,
                $remaining
            ),
            distanceM: $distanceM,
            remainingAttempts: $remaining
        );
    }

    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}