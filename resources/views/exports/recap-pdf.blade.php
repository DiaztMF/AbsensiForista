<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi FORISTA</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #2B1A14;
            margin: 16px;
        }
        .header {
            border-bottom: 2px solid #B92F25;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0 0 4px 0;
            font-size: 15px;
            color: #B92F25;
        }
        .meta {
            font-size: 9.5px;
            line-height: 1.5;
        }
        .summary-box {
            background-color: #FFF6E3;
            border: 1px solid #8A5A2E;
            padding: 8px 10px;
            margin-bottom: 12px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #B92F25;
            color: #FFFFFF;
            text-align: left;
            padding: 6px;
            border: 1px solid #B92F25;
        }
        td {
            padding: 5px 6px;
            border: 1px solid #D6CCC2;
        }
        tr:nth-child(even) td {
            background-color: #FAF7F2;
        }
        .badge-hadir {
            color: #0E8A45;
            font-weight: bold;
        }
        .badge-absen {
            color: #B92F25;
            font-weight: bold;
        }
        .footer {
            margin-top: 12px;
            font-size: 8.5px;
            color: #6B5B53;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FORUM OSIS SURAKARTA (FORISTA) — LAPORAN PRESENSI</h1>
        <div class="meta">
            @if ($mode === 'session')
                <strong>Agenda:</strong> {{ $data['meta']['agenda_title'] }} |
                <strong>Tanggal:</strong> {{ $data['meta']['session_date'] }}<br>
                <strong>Lokasi:</strong> {{ $data['meta']['location_name'] }} |
                <strong>Window Presensi:</strong> {{ $data['meta']['window_time'] }} |
                <strong>Filter Divisi:</strong> {{ $data['meta']['division_name'] }}
            @else
                <strong>Agenda:</strong> {{ $data['meta']['agenda_title'] }} |
                <strong>Periode:</strong> {{ $data['meta']['period_label'] }} |
                <strong>Filter Divisi:</strong> {{ $data['meta']['division_name'] }}
            @endif
        </div>
    </div>

    <div class="summary-box">
        @if ($mode === 'session')
            Total Anggota Aktif: {{ $data['summary']['total_active'] }}  |  
            Hadir: {{ $data['summary']['present_count'] }}
            (Otomatis: {{ $data['summary']['auto_count'] }}, Manual: {{ $data['summary']['manual_count'] }})  |  
            Belum Hadir: {{ $data['summary']['absent_count'] }}
        @else
            Total Anggota Aktif: {{ $data['summary']['total_active'] }}  |  
            Total Rapat Terlaksana: {{ $data['summary']['total_sessions'] }}  |  
            Rata-rata Kehadiran: {{ $data['summary']['average_percentage'] }}%  |  
            Rata-rata Skor: {{ number_format($data['summary']['average_score'], 1, '.', '') }} / 10.0
        @endif
    </div>

    @if ($mode === 'session')
        <table>
            <thead>
                <tr>
                    <th style="width: 28px;">No</th>
                    <th>Nama Lengkap</th>
                    <th>Divisi</th>
                    <th>Status</th>
                    <th>Waktu</th>
                    <th>Jarak</th>
                    <th>Sumber</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['rows'] as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['division'] }}</td>
                        <td class="{{ $row['status'] === 'hadir' ? 'badge-hadir' : 'badge-absen' }}">
                            {{ $row['status_label'] }}
                        </td>
                        <td>{{ $row['checked_at'] }}</td>
                        <td>{{ $row['distance_label'] }}</td>
                        <td>{{ $row['source_label'] }}</td>
                        <td>
                            @if ($row['manual_reason'])
                                {{ $row['manual_reason'] }} (oleh {{ $row['created_by_name'] }})
                            @elseif ($row['is_locked'])
                                Terkunci (3x di luar radius)
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 28px;">No</th>
                    <th>Nama Lengkap</th>
                    <th>Divisi</th>
                    <th>Total Rapat</th>
                    <th>Hadir</th>
                    <th>Persentase</th>
                    <th>Skor (1.0 - 10.0)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['rows'] as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['division'] }}</td>
                        <td>{{ $row['total_sessions'] }}</td>
                        <td>{{ $row['attended_count'] }}</td>
                        <td>{{ $row['percentage'] }}%</td>
                        <td><strong>{{ number_format($row['score'], 1, '.', '') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Dicetak otomatis oleh sistem Forista Attendance pada {{ $printedAt }}
    </div>
</body>
</html>
