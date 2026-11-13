<?php

namespace Tests\Feature\Auth;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_login_stores_flash_welcome_popup_for_members(): void
    {
        $member = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'username' => $member->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($member);
        $response->assertRedirect(route('member.dashboard', absolute: false));
        $this->assertTrue((bool) session('inertia.flash_data.show_welcome_popup'));
    }

    public function test_admins_are_redirected_to_admin_overview_and_can_still_open_member_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('login.store'), [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.overview', absolute: false));

        // F1 & F9: Admin juga berperan sebagai anggota dan dapat membuka layar Member
        $this->actingAs($admin)->get(route('member.dashboard'))->assertOk();
    }

    public function test_member_cannot_access_any_admin_routes(): void
    {
        $member = User::factory()->create();

        foreach ([
            'admin.overview',
            'admin.members.index',
            'admin.agendas.index',
            'admin.recaps.index',
            'admin.logs.index',
            'admin.settings.edit',
        ] as $route) {
            $this->actingAs($member)->get(route($route))->assertForbidden();
        }
    }

    public function test_deactivated_account_cannot_login(): void
    {
        $inactive = User::factory()->inactive()->create();

        $response = $this->post(route('login.store'), [
            'username' => $inactive->username,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_active_session_is_immediately_logged_out_when_account_is_deactivated(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('member.dashboard'))->assertOk();

        $member->update(['is_active' => false]);

        $response = $this->actingAs($member)->get(route('member.dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_throttled_after_five_attempts(): void
    {
        $user = User::factory()->create();
        RateLimiter::clear($user->username.'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        // Percobaan ke-6 ditolak 429 (bukan sekadar pesan validasi)
        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_admin_can_reset_member_password_and_old_password_stops_working(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $reason = 'Anggota lupa password saat rapat pleno.';

        $this->actingAs($admin)
            ->post(route('admin.members.reset-password', $member), [
                'password' => 'new-temp-pass',
                'reason' => $reason,
            ])
            ->assertRedirect();

        auth()->logout();

        $this->post(route('login.store'), [
            'username' => $member->username,
            'password' => 'old-password-123',
        ]);
        $this->assertGuest();

        $this->post(route('login.store'), [
            'username' => $member->username,
            'password' => 'new-temp-pass',
        ]);
        $this->assertAuthenticatedAs($member);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $member->id,
            'action' => AuditAction::PasswordReset->value,
            'reason' => $reason,
        ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
        }
}
