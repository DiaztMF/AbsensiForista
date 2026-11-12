<?php

namespace App\Actions\Auth;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetMemberPasswordAction
{
    public function execute(User $admin, User $member, string $newPassword, ?string $reason = null): void
    {
        $auditReason = trim($reason ?? '') !== ''
            ? trim($reason)
            : 'Reset password anggota oleh Admin.';

        DB::transaction(function () use ($admin, $member, $newPassword, $auditReason) {
            $member->forceFill([
                'password' => $newPassword,
                'remember_token' => Str::random(60),
            ])->save();

            // Hapus sesi aktif lama agar password lama langsung tidak berlaku
            DB::table('sessions')->where('user_id', $member->id)->delete();

            AuditLog::create([
                'actor_id' => $admin->id,
                'target_user_id' => $member->id,
                'action' => AuditAction::PasswordReset,
                'reason' => $auditReason,
                'metadata' => [
                    'username' => $member->username,
                ],
            ]);
        });
    }
}
