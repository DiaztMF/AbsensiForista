<?php

namespace Tests\Feature\Member;

use App\Actions\Attendance\CalculateMemberStatsAction;
use App\Enums\AttendanceSource;
use App\Enums\SessionStatus;
use App\Models\Agenda;
use App\Models\AgendaSession;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private function agenda(): Agenda
    {
        return Agenda::create([
            'title' => 'Rapat Rutin',
            'location_name' => 'Solo',
            'latitude' => -7.569548,
            'longitude' => 110.828316,
            'radius_m' => 20,
            'weekday' => 6,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'window_minutes' => 15,
            'starts_on' => now()->subMonths(6)->toDateString(),
            'is_active' => true,
        ]);
    }

    /** @return array<int, AgendaSession> */
    private function completedSessions(Agenda $agenda, int $count): array
    {
        $sessions = [];

        for ($i = 1; $i <= $count; $i++) {
            $date = now()->subWeeks($i);
            $sessions[] = AgendaSession::create([
                'agenda_id' => $agenda->id,
                'session_date' => $date->toDateString(),
                'starts_at' => $date->copy()->setTime(13, 0),
                'ends_at' => $date->copy()->setTime(15, 0),
                'window_opens_at' => $date->copy()->setTime(15, 0),
                'window_closes_at' => $date->copy()->setTime(15, 15),
                'status' => SessionStatus::Scheduled,
            ]);
        }

        return $sessions;
    }

    public function test_zero_attendance_gives_zero_percent_and_minimum_score_of_one(): void
    {
        $member = User::factory()->create();
        $this->completedSessions($this->agenda(), 5);

        $stats = app(CalculateMemberStatsAction::class)->execute($member);

        $this->assertSame(5, $stats['total_sessions']);
        $this->assertSame(0, $stats['attended_count']);
        $this->assertSame(0, $stats['percentage']);
        $this->assertSame(1.0, $stats['score']);
    }

    public function test_85_percent_attendance_gives_8_point_5_score_and_ignores_cancelled_sessions(): void
    {
        $member = User::factory()->create();
        $agenda = $this->agenda();
        $sessions = $this->completedSessions($agenda, 20);

        AgendaSession::create([
            'agenda_id' => $agenda->id,
            'session_date' => now()->subDays(2)->toDateString(),
            'starts_at' => now()->subDays(2)->setTime(13, 0),
            'ends_at' => now()->subDays(2)->setTime(15, 0),
            'window_opens_at' => now()->subDays(2)->setTime(15, 0),
            'window_closes_at' => now()->subDays(2)->setTime(15, 15),
            'status' => SessionStatus::Cancelled,
        ]);

        foreach (array_slice($sessions, 0, 17) as $session) {
            Attendance::create([
                'agenda_session_id' => $session->id,
                'user_id' => $member->id,
                'status' => 'hadir',
                'checked_at' => $session->window_opens_at->addMinutes(2),
                'distance_m' => 8.5,
                'source' => AttendanceSource::Auto,
            ]);
        }

        $stats = app(CalculateMemberStatsAction::class)->execute($member);

        $this->assertSame(20, $stats['total_sessions']);
        $this->assertSame(17, $stats['attended_count']);
        $this->assertSame(85, $stats['percentage']);
        $this->assertSame(8.5, $stats['score']);
    }

    public function test_member_dashboard_shows_only_authenticated_members_own_records(): void
    {
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        [$session1, $session2] = $this->completedSessions($this->agenda(), 2);

        Attendance::create([
            'agenda_session_id' => $session1->id,
            'user_id' => $memberA->id,
            'status' => 'hadir',
            'checked_at' => now()->subDays(7),
            'distance_m' => 5.0,
            'source' => AttendanceSource::Auto,
        ]);

        Attendance::create([
            'agenda_session_id' => $session2->id,
            'user_id' => $memberB->id,
            'status' => 'hadir',
            'checked_at' => now()->subDays(14),
            'distance_m' => 6.0,
            'source' => AttendanceSource::Auto,
        ]);

        $this->actingAs($memberA)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Dashboard')
                ->has('history', 1)
                ->where('stats.attended_count', 1));
    }
}
