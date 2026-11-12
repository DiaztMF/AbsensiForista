<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => AppSetting::current(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        AppSetting::current()->update($request->validated());

        return back()->with('success', 'Pengaturan global berhasil disimpan dan berlaku untuk sesi mendatang.');
    }
}
