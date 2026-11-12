<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:divisions,name'],
        ]);

        Division::create($validated);

        Inertia::flash('success', 'Divisi baru berhasil ditambahkan.');

        return back();
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('divisions', 'name')->ignore($division->id)],
        ]);

        $division->update($validated);

        Inertia::flash('success', 'Nama divisi berhasil diperbarui.');

        return back();
    }

    public function destroy(Division $division): RedirectResponse
    {
        Gate::authorize('delete', $division);

        $division->delete();

        Inertia::flash('success', 'Divisi berhasil dihapus.');

        return back();
    }
}
