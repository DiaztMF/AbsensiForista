<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceSource;
use App\Enums\SessionStatus;
use App\Models\Agenda;
use App\Models\AgendaSession;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaAndSessionTest extends TestCase
{
    use RefreshDatabase;

    private function agendaPayload(array $overrides = []): array
    {
        return [
            'title' => 'Rapat Mingguan FORISTA',
            'location_name' => 'Gedung Pemuda Surakarta',
            'address' => 'Jl. Adi Sucipto, Surakarta',
            'latitude' => -7.556123,
            'longitude' => 110.806456,
            'radius_m' => 25,
            'weekday' => now()->dayOfWeek,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'window_minutes' => 20,
            'starts_on' => now()->toDateString(),
            'is_active' => true,
            ...$overrides,
        ];
    }

    private function agenda(array $overrides = []): Agenda
    {
        return Agenda::create($this->agendaPayload($overrides));
    }

    public function test_creating_weekly_agenda_generates_sessions_for_next_four_weeks_without_duplicates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.agendas.store'), $this->agendaPayload())
            ->assertRedirect();

        $agenda = Agenda::firstOrFail();
        $sessionCount = $agenda->sessions()->count();

        $this->assertGreaterThanOrEqual(4, $sessionCount);

        $this->artisan('forista:generate-sessions')->assertSuccessful();
        $this->assertSame($sessionCount, $agenda->sessions()->count());
    }

    public function test_agenda_validation_rejects_invalid_coordinates_radius_and_window_length(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.agendas.store'), $this->agendaPayload([
                'latitude' => -105.0,
                'longitude' => 195.0,
                'radius_m' => 150,
                'window_minutes' => 2,
            ]))
            ->assertSessionHasErrors(['latitude', 'longitude', 'radius_m', 'window_minutes']);
    }

    public function test_editing_agenda_updates_only_future_sessions_and_preserves_past_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        $agenda = $this->agenda([
            'starts_on' => now()->subWeeks(2)->toDateString(),
        ]);

        $pastSession = AgendaSession::create([
            'agenda_id' => $agenda->id,
            'session_date' => now()->subWeek()->toDateString(),
            'starts_at' => now()->subWeek()->setTime(13, 0),
            'ends_at' => now()->subWeek()->setTime(15, 0),
            'window_opens_at' => now()->subWeek()->setTime(15, 0),
            'window_closes_at' => now()->subWeek()->setTime(15, 15),
            'status' => SessionStatus::Scheduled,
        ]);

        $futureSession = AgendaSession::create([
            'agenda_id' => $agenda->id,
            'session_date' => now()->addWeek()->toDateString(),
            'starts_at' => now()->addWeek()->setTime(13, 0),
            'ends_at' => now()->addWeek()->setTime(15, 0),
            'window_opens_at' => now()->addWeek()->setTime(15, 0),
            'window_closes_at' => now()->addWeek()->setTime(15, 15),
            'status' => SessionStatus::Scheduled,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.agendas.update', $agenda), $this->agendaPayload([
                'title' => 'Rapat Pleno Diperbarui',
                'end_time' => '16:00',
                'window_minutes' => 30,
                'starts_on' => now()->subWeeks(2)->toDateString(),
            ]))
            ->assertRedirect();

        $this->assertSame('15:00', $pastSession->fresh()->window_opens_at->format('H:i'));
        $this->assertSame('16:00', $futureSession->fresh()->window_opens_at->format('H:i'));
        $this->assertSame('16:30', $futureSession->fresh()->window_closes_at->format('H:i'));
    }

    public function test_shifting_single_session_window_updates_only_that_session_and_resets_reminder_flags(): void
    {
        $admin = User::factory()->admin()->create();
        $agenda = $this->agenda(['weekday' => 6, 'start_time' => '13:00', 'end_time' => '15:00', 'window_minutes' => 15]);

        $session = AgendaSession::create([
            'agenda_id' => $agenda->id,
            'session_date' => now()->toDateString(),
            'starts_at' => now()->subHours(2),
            'ends_at' => now(),
            'window_opens_at' => now(),
            'window_closes_at' => now()->addMinutes(15),
            'reminded_window_open_at' => now()->subMinute(),
            'status' => SessionStatus::Scheduled,
        ]);

        $newOpen = now()->addMinutes(30)->startOfMinute();
        $newClose = now()->addMinutes(50)->startOfMinute();

        $this->actingAs($admin)
            ->patch(route('admin.sessions.shift-window', $session), [
                'window_opens_at' => $newOpen->toDateTimeString(),
                'window_closes_at' => $newClose->toDateTimeString(),
            ])
            ->assertRedirect();

        $session->refresh();
        $this->assertSame($newOpen->timestamp, $session->window_opens_at->timestamp);
        $this->assertNull($session->reminded_window_open_at);
    }

    public function test_session_cannot_be_cancelled_after_check_ins_exist(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $agenda = $this->agenda();

        $busy = AgendaSession::create([
            'agenda_id' => $agenda->id,
            'session_date' => now()->toDateString(),
            'starts_at' => now()->subHours(2),
            'ends_at' => now(),
            'window_opens_at' => now(),
            'window_closes_at' => now()->addMinutes(15),
            'status' => SessionStatus::Scheduled,
        ]);

        Attendance::create([
            'agenda_session_id' => $busy->id,
            'user_id' => $member->id,
            'status' => 'hadir',
            'checked_at' => now(),
            'distance_m' => 5,
            'source' => AttendanceSource::Auto,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.sessions.cancel', $busy))
            ->assertForbidden();

        $empty = AgendaSession::create([
            'agenda_id' => $agenda->id,
            'session_date' => now()->addWeek()->toDateString(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'window_opens_at' => now()->addWeek()->addHours(2),
            'window_closes_at' => now()->addWeek()->addHours(2)->addMinutes(15),
            'status' => SessionStatus::Scheduled,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.sessions.cancel', $empty))
            ->assertRedirect();

        $this->assertSame(SessionStatus::Cancelled, $empty->fresh()->status);
    }
}
