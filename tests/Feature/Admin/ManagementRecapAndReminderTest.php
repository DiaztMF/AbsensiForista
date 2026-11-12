<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceSource;
use App\Enums\AuditAction;
use App\Enums\SessionStatus;
use App\Exports\RecapDataBuilder;
use App\Models\Agenda;
use App\Models\AgendaSession;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\User;
use App\Notifications\AgendaReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManagementRecapAndReminderTest extends TestCase
{
    use RefreshDatabase;

    private function agenda(): Agenda
    {
        return Agenda::create([
            'title' => 'Rapat Pleno',
            'location_name' => 'Solo',
            'latitude' => -7.569548,
            'longitude' => 110.828316,
            'radius_m' => 20,
            'weekday' => 6,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'window_minutes' => 15,
            'starts_on' => now()->toDateString(),
            'is_active' => true,
        ]);
    }

    protected function makeSession(Agenda $agenda, array $overrides = []): AgendaSession
    {
        return AgendaSession::create([
            'agenda_id' => $agenda->id,
            'session_date' => now()->toDateString(),
            'starts_at' => now()->subHours(2),
            'ends_at' => now(),
            'window_opens_at' => now(),
            'window_closes_at' => now()->addMinutes(15),
            'status' => SessionStatus::Scheduled,
            ...$overrides,
        ]);
    }

    public function test_division_with_assigned_members_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $populated = Division::create(['name' => 'Humas']);
        $empty = Division::create(['name' => 'Logistik']);

        User::factory()->create(['division_id' => $populated->id]);

        $this->actingAs($admin)
            ->delete(route('admin.divisions.destroy', $populated))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.divisions.destroy', $empty))
            ->assertRedirect();

        $this->assertDatabaseMissing('divisions', ['id' => $empty->id]);
    }

    public function test_manual_attendance_requires_minimum_10_chars_reason_writes_audit_log_and_prevents_duplicate(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $session = $this->makeSession($this->agenda());

        $this->actingAs($admin)
            ->post(route('admin.recaps.manual', $session), [
                'user_id' => $member->id,
                'reason' => 'HP mati',
            ])
            ->assertSessionHasErrors('reason');

        $reason = 'Hadir di ruangan tetapi GPS HP error terus.';

        $this->actingAs($admin)
            ->post(route('admin.recaps.manual', $session), [
                'user_id' => $member->id,
                'reason' => $reason,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'agenda_session_id' => $session->id,
            'user_id' => $member->id,
            'source' => AttendanceSource::Manual->value,
            'manual_reason' => $reason,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $member->id,
            'action' => AuditAction::ManualAttendance->value,
            'reason' => $reason,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.recaps.manual', $session), [
                'user_id' => $member->id,
                'reason' => 'Percobaan input ganda kedua kalinya.',
            ])
            ->assertSessionHasErrors('manual');
    }

    public function test_admins_appear_in_recaps_and_exports_succeed(): void
    {
        $admins = User::factory()->count(3)->admin()->create();
        $members = User::factory()->count(4)->create();
        $session = $this->makeSession($this->agenda());

        foreach ([$admins[0], $members[0]] as $present) {
            Attendance::create([
                'agenda_session_id' => $session->id,
                'user_id' => $present->id,
                'status' => 'hadir',
                'checked_at' => now(),
                'distance_m' => 5,
                'source' => AttendanceSource::Auto,
            ]);
        }

        Attendance::create([
            'agenda_session_id' => $session->id,
            'user_id' => $members[1]->id,
            'status' => 'hadir',
            'checked_at' => now(),
            'distance_m' => null,
            'source' => AttendanceSource::Manual,
            'manual_reason' => 'Kendala perangkat saat rapat berlangsung',
            'created_by' => $admins[0]->id,
        ]);

        $recap = app(RecapDataBuilder::class)->buildSessionRecap($session);

        $this->assertSame(7, $recap['summary']['total_active']);
        $this->assertSame(3, $recap['summary']['present_count']);
        $this->assertSame(4, $recap['summary']['absent_count']);
        $this->assertSame(2, $recap['summary']['auto_count']);
        $this->assertSame(1, $recap['summary']['manual_count']);

        $this->actingAs($admins[0])
            ->get(route('admin.recaps.export', ['format' => 'xlsx', 'session_id' => $session->id]))
            ->assertOk();

        $this->actingAs($admins[0])
            ->get(route('admin.recaps.export', ['format' => 'pdf', 'session_id' => $session->id]))
            ->assertOk();
    }

    public function test_reminder_scheduler_sends_each_reminder_once_and_skips_present_or_inactive_users(): void
    {
        Notification::fake();
        AppSetting::current();

        $present = User::factory()->create();
        $pending = User::factory()->create();
        $inactive = User::factory()->inactive()->create();
        $agenda = $this->agenda();

        $session = $this->makeSession($agenda, [
            'ends_at' => now()->subMinutes(11),
            'window_opens_at' => now()->subMinutes(11),
            'window_closes_at' => now()->addMinutes(4),
            'reminded_before_start_at' => now()->subHours(2)->subMinutes(30),
            'reminded_window_open_at' => now()->subMinutes(11),
            'reminded_before_close_at' => null,
        ]);

        Attendance::create([
            'agenda_session_id' => $session->id,
            'user_id' => $present->id,
            'status' => 'hadir',
            'checked_at' => now()->subMinutes(5),
            'distance_m' => 5,
            'source' => AttendanceSource::Auto,
        ]);

        $this->artisan('forista:dispatch-reminders')->assertSuccessful();

        Notification::assertSentTo($pending, AgendaReminderNotification::class);
        Notification::assertNotSentTo($present, AgendaReminderNotification::class);
        Notification::assertNotSentTo($inactive, AgendaReminderNotification::class);

        // Idempoten: eksekusi berikutnya tidak mengirim ulang
        Notification::fake();
        $this->artisan('forista:dispatch-reminders')->assertSuccessful();
        Notification::assertNothingSent();
    }
}
