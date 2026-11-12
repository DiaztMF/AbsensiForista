<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __invoke(Request $request, string $format): RedirectResponse
    {
        // TODO Tahap 4: Implementasi AttendanceRecapExport untuk xlsx & pdf
        return back()->withErrors(['export' => 'Ekspor '.strtoupper($format).' akan tersedia pada Tahap 4.']);
    }
}
