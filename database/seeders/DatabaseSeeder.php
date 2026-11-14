<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatabaseSeeder extends Seeder
{
    /** Warna divisi FORISTA (hex). */
    private const DIVISION_COLORS = [
        'BPH' => '#EF4444', // Merah
        'PENDIKBUD' => '#F97316', // Orange
        'AGSOS' => '#EAB308', // Kuning
        'DESMED' => '#14B8A6', // Biru Toska (Turquoise)
        'PARTNERSHIP' => '#22C55E', // Hijau
        'USDAN' => '#A855F7', // Ungu
        'HUMAS' => '#3B82F6', // Biru Muda
        'PSDM' => '#FB923C', // Peach
        'KAMPANYE' => '#84CC16', // Hijau Neon
    ];

    /** Mapping nama divisi dari Excel ke kunci divisi FORISTA di atas. */
    private const DIVISION_MAP = [
        // BPH — Badan Pengurus Harian
        'Ketua Umum' => 'BPH',
        'Ketua 1' => 'BPH',
        'Ketua 2' => 'BPH',
        'Sekretaris Umum' => 'BPH',
        'Sekretaris 1' => 'BPH',
        'Sekretaris 2' => 'BPH',
        'Bendahara 1' => 'BPH',
        'Bendahara 2' => 'BPH',
        'Koordinator Keuangan' => 'BPH',
        'Koordinator Pembangunan' => 'BPH',
        'Koordinator Humkominfo' => 'HUMAS',
        '' => 'BPH', // anggota tanpa divisi

        // PENDIKBUD — Pendidikan dan Kebudayaan
        'Pendidikan dan Kebudayaan' => 'PENDIKBUD',
        'Kadiv Pendidikan dan Kebudayaan' => 'PENDIKBUD',

        // AGSOS — Agama Sosial
        'Agama Sosial' => 'AGSOS',
        'Kadiv Agama Sosial' => 'AGSOS',

        // DESMED — Desain Media
        'Desain Media' => 'DESMED',
        'Kadiv Desain Media' => 'DESMED',

        // PARTNERSHIP
        'Partnership' => 'PARTNERSHIP',
        'Kadiv Partnership' => 'PARTNERSHIP',

        // USDAN — Usaha Dana
        'Usaha Dana' => 'USDAN',
        'Kadiv Usaha Dana' => 'USDAN',

        // HUMAS
        'Humas' => 'HUMAS',
        'Kadiv Humas' => 'HUMAS',

        // PSDM
        'PSDM' => 'PSDM',
        'Kadiv PSDM' => 'PSDM',

        // KAMPANYE
        'Kampanye' => 'KAMPANYE',
        'Kadiv Kampanye' => 'KAMPANYE',
    ];

    public function run(): void
    {
        AppSetting::current();

        // 1. Buat 9 divisi FORISTA dengan warna.
        $divisions = [];
        foreach (self::DIVISION_COLORS as $key => $color) {
            $divisions[$key] = Division::firstOrCreate(
                ['name' => $key],
                ['color' => $color]
            )->fresh();
        }

        // 2. Akun admin inti (divisi BPH).
        $adminPassword = getenv('SEEDER_ADMIN_PASSWORD') ?: Str::password(16);
        $admins = [
            ['name' => 'Admin Ketua FORISTA', 'username' => 'admin.ketua'],
            ['name' => 'Admin Sekretaris FORISTA', 'username' => 'admin.sekretaris'],
            ['name' => 'Admin Bendahara FORISTA', 'username' => 'admin.bendahara'],
        ];

        foreach ($admins as $admin) {
            User::firstOrCreate(
                ['username' => $admin['username']],
                [
                    'name' => $admin['name'],
                    'division_id' => $divisions['BPH']->id,
                    'password' => bcrypt($adminPassword),
                    'role' => UserRole::Admin,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // 3. Baca data anggota dari Excel.
        // Path bisa dioverride lewat env SEEDER_EXCEL_PATH agar berjalan di VPS/Linux.
        $excelPath = getenv('SEEDER_EXCEL_PATH')
            ?: base_path('database/seeders/data/Data Absensi Forista.xlsx');
        if (! file_exists($excelPath)) {
            $this->command?->warn("File Excel tidak ditemukan: {$excelPath}");
            $this->command?->info("Kata sandi admin seeder: {$adminPassword}");

            return;
        }

        $spreadsheet = IOFactory::load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $memberPassword = getenv('SEEDER_MEMBER_PASSWORD') ?: 'member2026';
        $created = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            $name = trim($row['B'] ?? '');
            $username = trim($row['D'] ?? '');
            $excelDivision = trim($row['C'] ?? '');
            $rawPassword = trim($row['E'] ?? '');

            if ($name === '' || $username === '') {
                $skipped++;

                continue;
            }

            // Map divisi Excel ke kunci FORISTA.
            $divisionKey = self::DIVISION_MAP[$excelDivision] ?? 'BPH';
            $divisionId = $divisions[$divisionKey]->id;

            // Generate email dari username.
            $email = strtolower($username).'@forista.or.id';

            User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'division_id' => $divisionId,
                    'password' => bcrypt($rawPassword ?: $memberPassword),
                    'role' => UserRole::Member,
                    'is_active' => true,
                    'email' => $email,
                    'email_verified_at' => now(),
                ]
            );
            $created++;
        }

        $this->command?->info("Seeder selesai: {$created} anggota dibuat, {$skipped} dilewati.");
        $this->command?->info("Kata sandi admin seeder: {$adminPassword}");
    }
}
