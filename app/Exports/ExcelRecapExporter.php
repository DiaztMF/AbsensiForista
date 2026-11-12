<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelRecapExporter
{
    public function downloadSessionRecap(array $data, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Sesi');

        // Judul & Metadata
        $sheet->setCellValue('A1', 'REKAP PRESENSI FORUM OSIS SURAKARTA (FORISTA)');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FF2B1A14');

        $sheet->setCellValue('A2', "Agenda: {$data['meta']['agenda_title']} | Tanggal: {$data['meta']['session_date']}");
        $sheet->setCellValue('A3', "Lokasi: {$data['meta']['location_name']} | Window: {$data['meta']['window_time']} | Filter: {$data['meta']['division_name']}");
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        // Header Tabel
        $headers = ['No', 'Nama Lengkap', 'Divisi', 'Status', 'Waktu Presensi', 'Jarak', 'Sumber', 'Keterangan / Alasan Manual'];
        $sheet->fromArray($headers, null, 'A5');
        $this->styleHeaderRow($sheet, 'A5:H5');

        $rowNum = 6;
        foreach ($data['rows'] as $index => $row) {
            $note = $row['manual_reason']
                ? "{$row['manual_reason']} (oleh {$row['created_by_name']})"
                : ($row['is_locked'] ? 'Terkunci (3x di luar radius)' : '-');

            $sheet->fromArray([
                $index + 1,
                $row['name'],
                $row['division'],
                $row['status_label'],
                $row['checked_at'],
                $row['distance_label'],
                $row['source_label'],
                $note,
            ], null, "A{$rowNum}");

            $rowNum++;
        }

        // Baris Total (Sinkron dengan layar)
        $summary = $data['summary'];
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->mergeCells("A{$rowNum}:C{$rowNum}");
        $sheet->setCellValue(
            "D{$rowNum}",
            "Hadir: {$summary['present_count']} | Belum hadir: {$summary['absent_count']} (Total: {$summary['total_active']})"
        );
        $sheet->mergeCells("D{$rowNum}:H{$rowNum}");
        $this->styleSummaryRow($sheet, "A{$rowNum}:H{$rowNum}");

        $this->applyTableBorders($sheet, "A5:H{$rowNum}");
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    public function downloadRangeRecap(array $data, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Periode');

        $sheet->setCellValue('A1', 'REKAP KEHADIRAN & SKOR ANGGOTA FORISTA');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FF2B1A14');

        $sheet->setCellValue('A2', "Agenda: {$data['meta']['agenda_title']} | Periode: {$data['meta']['period_label']} | Filter: {$data['meta']['division_name']}");
        $sheet->mergeCells('A2:G2');

        $headers = ['No', 'Nama Lengkap', 'Divisi', 'Total Rapat', 'Jumlah Hadir', 'Persentase (%)', 'Skor (1.0 - 10.0)'];
        $sheet->fromArray($headers, null, 'A4');
        $this->styleHeaderRow($sheet, 'A4:G4');

        $rowNum = 5;
        foreach ($data['rows'] as $index => $row) {
            $sheet->fromArray([
                $index + 1,
                $row['name'],
                $row['division'],
                $row['total_sessions'],
                $row['attended_count'],
                $row['percentage'].'%',
                number_format($row['score'], 1, '.', ''),
            ], null, "A{$rowNum}");

            $rowNum++;
        }

        $summary = $data['summary'];
        $sheet->setCellValue("A{$rowNum}", 'RATA-RATA / TOTAL ANGGOTA AKTIF');
        $sheet->mergeCells("A{$rowNum}:C{$rowNum}");
        $sheet->setCellValue("D{$rowNum}", $summary['total_sessions']);
        $sheet->setCellValue("E{$rowNum}", "{$summary['total_active']} Anggota");
        $sheet->setCellValue("F{$rowNum}", $summary['average_percentage'].'%');
        $sheet->setCellValue("G{$rowNum}", number_format($summary['average_score'], 1, '.', ''));
        $this->styleSummaryRow($sheet, "A{$rowNum}:G{$rowNum}");

        $this->applyTableBorders($sheet, "A4:G{$rowNum}");
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    private function styleHeaderRow($sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFB92F25');
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function styleSummaryRow($sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setARGB('FF2B1A14');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF6E3');
    }

    private function applyTableBorders($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFD6CCC2');
    }

    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
