<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DivisionPolicy
{
    /**
     * F9: Divisi yang masih memiliki anggota tidak boleh dihapus.
     */
    public function delete(User $user, Division $division): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Hanya Admin yang dapat menghapus divisi.');
        }

        if ($division->users()->exists()) {
            return Response::deny('Divisi tidak dapat dihapus karena masih memiliki anggota terdaftar.');
        }

        return Response::allow();
    }
}
