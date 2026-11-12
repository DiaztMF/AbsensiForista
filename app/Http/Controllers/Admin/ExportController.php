<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExcelRecapExporter;
use App\Exports\RecapDataBuilder;
use App\Http\Controllers\Controller;
use App\Models\AgendaSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __invoke(
        Request $request,
        string $format,
        RecapDataBuilder $builder,
        ExcelRecapExporter $excelExporter
    ): Response {
        $mode = $request->query('mode', 'session'); // 'session' | 'range'
        $agendaId = $request->integer('agenda_id') ?: null;
        $sessionId = $request->integer('session_id') ?: null;
        $divisionId = $request->integer('division_id') ?: null;
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $timestamp = now()->format('Ymd-His');

        if ($mode === 'range') {
            $data = $builder->buildRangeRecap($agendaId, $divisionId, $fromDate, $toDate);
            $filename = "rekap-periode-forista-{$timestamp}.{$format}";

            if ($format === 'xlsx') {
                return $excelExporter->downloadRangeRecap($data, $filename);
            }

            return Pdf::loadView('exports.recap-pdf', [
                'mode' => 'range',
                'data' => $data,
                'printedAt' => now()->format('d M Y, H:i').' WIB',
            ])->setPaper('a4', 'portrait')->download($filename);
        }

        $session = $sessionId ? AgendaSession::with('agenda')->find($sessionId) : null;
        $data = $builder->buildSessionRecap($session, $divisionId);
        $filename = "rekap-sesi-forista-{$timestamp}.{$format}";

        if ($format === 'xlsx') {
            return $excelExporter->downloadSessionRecap($data, $filename);
        }

        return Pdf::loadView('exports.recap-pdf', [
            'mode' => 'session',
            'data' => $data,
            'printedAt' => now()->format('d M Y, H:i').' WIB',
        ])->setPaper('a4', 'landscape')->download($filename);
    }
}
