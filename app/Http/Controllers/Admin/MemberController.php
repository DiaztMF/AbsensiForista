<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\CalculateMemberStatsAction;
use App\Actions\Auth\ResetMemberPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request, CalculateMemberStatsAction $statsAction): Response
    {
        $search = trim((string) $request->query('search', ''));
        $divisionId = $request->query('division_id');

        $members = User::with('division')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    // whereLike portabel lintas driver (SQLite & Postgres)
                    $sub->whereLike('name', "%{$search}%", caseSensitive: false)
                        ->orWhereLike('username', "%{$search}%", caseSensitive: false);
                });
            })
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'username' => $member->username,
                'role' => $member->role->value,
                'is_active' => $member->is_active,
                'division' => [
                    'id' => $member->division->id,
                    'name' => $member->division->name,
                ],
                'stats' => $statsAction->execute($member),
            ]);

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'division_id' => $divisionId ? (int) $divisionId : null,
            ],
        ]);
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        Inertia::flash('success', 'Akun anggota berhasil dibuat.');

        return back();
    }

    public function update(UpdateMemberRequest $request, User $member): RedirectResponse
    {
        $member->update($request->validated());

        Inertia::flash('success', 'Data anggota berhasil diperbarui.');

        return back();
    }

    public function toggleActive(Request $request, User $member): RedirectResponse
    {
        if ($request->user()->id === $member->id) {
            return back()->withErrors(['member' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        $member->update(['is_active' => ! $member->is_active]);

        $statusLabel = $member->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        Inertia::flash('success', "Akun {$member->name} berhasil {$statusLabel}.");

        return back();
    }

    public function resetPassword(
        Request $request,
        User $member,
        ResetMemberPasswordAction $resetAction
    ): RedirectResponse {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        $resetAction->execute(
            admin: $request->user(),
            member: $member,
            newPassword: $validated['password'],
            reason: $validated['reason'] ?? null
        );

        Inertia::flash('success', "Kata sandi {$member->name} berhasil direset.");

        return back();
    }
}
