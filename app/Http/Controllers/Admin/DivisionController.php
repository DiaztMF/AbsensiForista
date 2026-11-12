<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
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

        return back()->with('success', 'Divisi baru berhasil ditambahkan.');
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('divisions', 'name')->ignore($division->id)],
        ]);

        $division->update($validated);

        return back()->with('success', 'Nama divisi berhasil diperbarui.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        Gate::authorize('delete', $division);

        $division->delete();

        return back()->with('success', 'Divisi berhasil dihapus.');
    }
}
