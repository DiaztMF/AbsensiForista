<?php

namespace Tests\Feature\Member;

use App\Enums\AttemptResult;
use App\Enums\AttendanceSource;
use App\Enums\SessionStatus;
use App\Models\Agenda;
use App\Models\AgendaSession;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    private Agenda $agenda;

    private AgendaSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::current();

        $this->agenda = Agenda::create([
            'title' => 'Rapat Pleno FORISTA',
            'location_name' => 'Balaikota Surakarta',
            'latitude' => -7.569548,
            'longitude' => 110.828316,
            'radius_m' => 20,
            'weekday' => now()->dayOfWeek,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'window_minutes' => 15,
            'starts_on' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->session = AgendaSession::create([
            'agenda_id' => $this->agenda->id,
            'session_date' => now()->toDateString(),
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subMinute(),
            'window_opens_at' => now()->subMinute(),
            'window_closes_at' => now()->addMinutes(14),
            'status' => SessionStatus::Scheduled,
        ]);
    }

    /**
     * Flash check-in kini dikirim lewat Inertia (bukan session key biasa).
     */
    private function assertCheckinResult(array $expected, ?\Closure $matcher = null): void
    {
        $result = session('inertia.flash_data.checkin_result');

        $this->assertIsArray($result, 'Flash checkin_result tidak ditemukan.');

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result[$key] ?? null, "Nilai {$key} tidak sesuai.");
        }

        if ($matcher !== null) {
            $this->assertTrue($matcher($result));
        }
    }

    private function payload(float $lat = -7.569548, float $lng = 110.828316, float $accuracy = 10): array
    {
        return ['latitude' => $lat, 'longitude' => $lng, 'accuracy_m' => $accuracy];
    }

    public function test_check_in_is_rejected_before_window_opens(): void
    {
        $member = User::factory()->create();

        $this->session->update([
            'window_opens_at' => now()->addMinutes(10),
            'window_closes_at' => now()->addMinutes(25),
        ]);

        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $this->payload());
        $this->assertCheckinResult([], fn (array $res) => $res['success'] === false && $res['code'] === 'window_not_open');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_check_in_is_rejected_after_window_closes(): void
    {
        $member = User::factory()->create();

        $this->session->update([
            'window_opens_at' => now()->subMinutes(30),
            'window_closes_at' => now()->subMinutes(15),
        ]);

        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $this->payload());
        $this->assertCheckinResult([], fn (array $res) => $res['success'] === false && $res['code'] === 'window_closed');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_member_within_radius_and_good_accuracy_checks_in_successfully(): void
    {
        $member = User::factory()->create();

        // ~5 meter dari titik pusat agenda
        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $this->payload(-7.569510, 110.828316, 12.5));
        $this->assertCheckinResult([], fn (array $res) => $res['success'] === true
                && $res['code'] === 'matched'
                && $res['message'] === 'Presensi berhasil!');

        $this->assertDatabaseHas('attendances', [
            'agenda_session_id' => $this->session->id,
            'user_id' => $member->id,
            'status' => 'hadir',
            'source' => AttendanceSource::Auto->value,
        ]);

        $this->assertDatabaseHas('attendance_attempts', [
            'agenda_session_id' => $this->session->id,
            'user_id' => $member->id,
            'result' => AttemptResult::Matched->value,
        ]);

        // Privasi: koordinat mentah tidak pernah disimpan
        $this->assertFalse(Schema::hasColumn('attendances', 'latitude'));
        $this->assertFalse(Schema::hasColumn('attendance_attempts', 'latitude'));
    }

    public function test_poor_gps_accuracy_is_rejected_without_using_attempt_quota(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $this->payload(accuracy: 35));
        $this->assertCheckinResult([], fn (array $res) => $res['success'] === false
                && $res['code'] === 'poor_accuracy'
                && $res['remaining_attempts'] === 3);

        $this->assertDatabaseHas('attendance_attempts', [
            'agenda_session_id' => $this->session->id,
            'user_id' => $member->id,
            'result' => AttemptResult::PoorAccuracy->value,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_three_outside_radius_attempts_lock_the_member_for_that_session(): void
    {
        $member = User::factory()->create();
        $far = $this->payload(-7.568200, 110.828316, 8);

        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $far);
        $this->assertCheckinResult([], fn (array $r) => $r['code'] === 'outside_radius' && $r['remaining_attempts'] === 2);

        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $far);
        $this->assertCheckinResult([], fn (array $r) => $r['code'] === 'outside_radius' && $r['remaining_attempts'] === 1);

        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $far);
        $this->assertCheckinResult([], fn (array $r) => $r['code'] === 'attempts_locked' && $r['remaining_attempts'] === 0);

        // Percobaan ke-4 di titik benar tetap ditolak karena sudah terkunci
        $this->actingAs($member)
            ->post(route('member.checkin', $this->session), $this->payload(accuracy: 5));
        $this->assertCheckinResult([], fn (array $r) => $r['code'] === 'attempts_locked');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_second_check_in_for_same_session_creates_no_duplicate_record(): void
    {
        $member = User::factory()->create();
        $payload = $this->payload(accuracy: 6);

        $this->actingAs($member)->post(route('member.checkin', $this->session), $payload);
        $this->actingAs($member)->post(route('member.checkin', $this->session), $payload);

        $this->assertSame(
            1,
            Attendance::where('agenda_session_id', $this->session->id)
                ->where('user_id', $member->id)
                ->count()
        );
    }

    public function test_admin_can_also_check_in_as_a_member(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('member.checkin', $this->session), $this->payload(accuracy: 8));
        $this->assertCheckinResult([], fn (array $r) => $r['success'] === true);

        $this->assertDatabaseHas('attendances', [
            'agenda_session_id' => $this->session->id,
            'user_id' => $admin->id,
        ]);
    }
}
