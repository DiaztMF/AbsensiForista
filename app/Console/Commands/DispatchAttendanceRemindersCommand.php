<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\AgendaSession;
use App\Models\AppSetting;
use App\Models\User;
use App\Notifications\AgendaReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class DispatchAttendanceRemindersCommand extends Command
{
    protected $signature = 'forista:dispatch-reminders';

    protected $description = 'Mengirim pengingat sebelum rapat, saat check-in dibuka, dan 5 menit sebelum ditutup.';

    public function handle(): int
    {
        $now = now();
        $settings = AppSetting::current();
        $activeUsers = User::active()->get();

        if ($activeUsers->isEmpty()) {
            return self::SUCCESS;
        }

        // 1. Pengingat sebelum rapat dimulai (default 30 menit sebelumnya)
        if ($settings->remind_before_start_minutes > 0) {
            $beforeStartSessions = AgendaSession::with('agenda')
                ->where('status', SessionStatus::Scheduled)
                ->whereNull('reminded_before_start_at')
                ->where('starts_at', '>', $now)
                ->where('starts_at', '<=', $now->copy()->addMinutes($settings->remind_before_start_minutes))
                ->get();

            foreach ($beforeStartSessions as $session) {
                $updated = AgendaSession::whereKey($session->id)
                    ->whereNull('reminded_before_start_at')
                    ->update(['reminded_before_start_at' => $now]);

                if ($updated) {
                    Notification::send(
                        $activeUsers,
                        new AgendaReminderNotification(
                            session: $session,
                            title: 'Rapat Segera Dimulai',
                            body: "{$session->agenda->title} di {$session->agenda->location_name} dimulai pukul {$session->starts_at->format('H:i')} WIB."
                        )
                    );
                }
            }
        }

        // 2. Pengingat saat check-in window dibuka
        if ($settings->remind_on_window_open) {
            $windowOpenSessions = AgendaSession::with('agenda')
                ->where('status', SessionStatus::Scheduled)
                ->whereNull('reminded_window_open_at')
                ->where('window_opens_at', '<=', $now)
                ->where('window_closes_at', '>', $now)
                ->get();

            foreach ($windowOpenSessions as $session) {
                $updated = AgendaSession::whereKey($session->id)
                    ->whereNull('reminded_window_open_at')
                    ->update(['reminded_window_open_at' => $now]);

                if ($updated) {
                    Notification::send(
                        $activeUsers,
                        new AgendaReminderNotification(
                            session: $session,
                            title: 'Presensi Dibuka!',
                            body: "Presensi {$session->agenda->title} sudah dibuka hingga pukul {$session->window_closes_at->format('H:i')} WIB."
                        )
                    );
                }
            }
        }

        // 3. Pengingat 5 menit sebelum window ditutup (hanya untuk yang belum hadir sesuai F6)
        if ($settings->remind_before_close_minutes > 0) {
            $beforeCloseSessions = AgendaSession::with('agenda')
                ->where('status', SessionStatus::Scheduled)
                ->whereNull('reminded_before_close_at')
                ->where('window_closes_at', '>', $now)
                ->where('window_closes_at', '<=', $now->copy()->addMinutes($settings->remind_before_close_minutes))
                ->get();

            foreach ($beforeCloseSessions as $session) {
                $updated = AgendaSession::whereKey($session->id)
                    ->whereNull('reminded_before_close_at')
                    ->update(['reminded_before_close_at' => $now]);

                if ($updated) {
                    $presentIds = $session->attendances()->pluck('user_id');
                    $notYetPresent = $activeUsers->whereNotIn('id', $presentIds);

                    if ($notYetPresent->isNotEmpty()) {
                        Notification::send(
                            $notYetPresent,
                            new AgendaReminderNotification(
                                session: $session,
                                title: 'Sisa Waktu Presensi Hampir Habis!',
                                body: "Waktu presensi {$session->agenda->title} akan ditutup pada {$session->window_closes_at->format('H:i')} WIB."
                            )
                        );
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
