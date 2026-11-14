<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Warna divisi FORISA (hex). */
    private const DIVISION_COLORS = [
        'BPH'        => '#EF4444', // Merah
        'PENDIKBUD'  => '#F97316', // Orange
        'AGSOS'      => '#EAB308', // Kuning
        'DESMED'     => '#14B8A6', // Biru Toska (Turquoise)
        'PARTNERSHIP'=> '#22C55E', // Hijau
        'USDAN'      => '#A855F7', // Ungu
        'HUMAS'      => '#3B82F6', // Biru Muda
        'PSDM'       => '#FB923C', // Peach
        'KAMPANYE'   => '#84CC16', // Hijau Neon
    ];

    /** Mapping nama divisi dari Excel ke kunci divisi FORISA. */
    private const DIVISION_MAP = [
        // BPH — Badan Pengurus Harian
        'Ketua Umum'        => 'BPH',
        'Ketua 1'           => 'BPH',
        'Ketua 2'           => 'BPH',
        'Sekretaris Umum'   => 'BPH',
        'Sekretaris 1'      => 'BPH',
        'Sekretaris 2'      => 'BPH',
        'Bendahara 1'       => 'BPH',
        'Bendahara 2'       => 'BPH',
        'Koordinator Keuangan'=> 'BPH',
        'Koordinator Pembangunan' => 'BPH',
        'Koordinator Humkominfo'=> 'HUMAS',
        ''                  => 'BPH', // anggota tanpa divisi

        // PENDIKBUD — Pendidikan dan Kebudayaan
        'Pendidikan dan Kebudayaan' => 'PENDIKBUD',
        'Kadiv Pendidikan dan Kebudayaan' => 'PENDIKBUD',

        // AGSOS — Agama Sosial
        'Agama Sosial'      => 'AGSOS',
        'Kadiv Agama Sosial'=> 'AGSOS',

        // DESMED — Desain Media
        'Desain Media'      => 'DESMED',
        'Kadiv Desain Media'=> 'DESMED',

        // PARTNERSHIP
        'Partnership'       => 'PARTNERSHIP',
        'Kadiv Partnership' => 'PARTNERSHIP',

        // USDAN — Usaha Dana
        'Usaha Dana'        => 'USDAN',
        'Kadiv Usaha Dana'  => 'USDAN',

        // HUMAS
        'Humas'             => 'HUMAS',
        'Kadiv Humas'       => 'HUMAS',

        // PSDM
        'PSDM'              => 'PSDM',
        'Kadiv PSDM'        => 'PSDM',

        // KAMPANYE
        'Kampanye'          => 'KAMPANYE',
        'Kadiv Kampanye'    => 'KAMPANYE',
    ];

    /** Data anggota diekstrak dari file Excel "Data Absensi Forista.xlsx". */
    private const MEMBERS = [
        ['name' => 'Madeline Patrisha Kurniawan', 'username' => 'MadelineFosis1', 'divisi' => 'Ketua Umum', 'password' => 'Kurniawan27Forista'],
        ['name' => 'Ken Pramodhawardani', 'username' => 'KenFosis2', 'divisi' => 'Ketua 1', 'password' => 'Pramodhawardani14Forista'],
        ['name' => 'Muhammad Fahmi Althafur Rochman', 'username' => 'MuhammadFosis3', 'divisi' => 'Ketua 2', 'password' => 'Rochman83Forista'],
        ['name' => 'Raihan Nur Gunawan Ar-Rasyad', 'username' => 'RaihanFosis4', 'divisi' => 'Sekretaris Umum', 'password' => 'ArRasyad52Forista'],
        ['name' => 'Putri Joan Santoso', 'username' => 'PutriFosis5', 'divisi' => 'Sekretaris 1', 'password' => 'Santoso19Forista'],
        ['name' => 'Melvin Nico Wibowo', 'username' => 'MelvinFosis6', 'divisi' => 'Sekretaris 2', 'password' => 'Wibowo64Forista'],
        ['name' => 'Ahlan Fadli Azri', 'username' => 'AhlanFosis7', 'divisi' => 'Bendahara 1', 'password' => 'Azri31Forista'],
        ['name' => 'Bias Bilqis Candra Ayu', 'username' => 'BiasFosis8', 'divisi' => 'Bendahara 2', 'password' => 'Ayu76Forista'],
        ['name' => 'Farah Naviza', 'username' => 'FarahFosis9', 'divisi' => 'Koordinator Humkominfo', 'password' => 'Naviza42Forista'],
        ['name' => 'Puri Kirana Kriswardani', 'username' => 'PuriFosis10', 'divisi' => 'Koordinator Keuangan', 'password' => 'Kriswardani91Forista'],
        ['name' => 'Athaya Alzena Zahra', 'username' => 'AthayaFosis11', 'divisi' => 'Koordinator Pembangunan', 'password' => 'Zahra23Forista'],
        ['name' => 'Deandra Felicya Joanecta Hatu', 'username' => 'DeandraFosis12', 'divisi' => 'Kadiv Pendidikan dan Kebudayaan', 'password' => 'Hatu58Forista'],
        ['name' => 'Agustinna Bintank Prasistri', 'username' => 'AgustinnaFosis13', 'divisi' => 'Pendidikan dan Kebudayaan', 'password' => 'Prasistri37Forista'],
        ['name' => 'Ikhsan Aria Bima', 'username' => 'IkhsanFosis14', 'divisi' => 'Pendidikan dan Kebudayaan', 'password' => 'Bima82Forista'],
        ['name' => 'Ratu Dewi Kencana Sari', 'username' => 'RatuFosis15', 'divisi' => 'Pendidikan dan Kebudayaan', 'password' => 'Sari16Forista'],
        ['name' => 'Kalila Galuh Ayu Maharani', 'username' => 'KalilaFosis16', 'divisi' => 'Pendidikan dan Kebudayaan', 'password' => 'Maharani49Forista'],
        ['name' => 'Chrisave Nadhine N', 'username' => 'ChrisaveFosis17', 'divisi' => 'Pendidikan dan Kebudayaan', 'password' => 'N28Forista'],
        ['name' => 'Cinta Putri Rahmadani', 'username' => 'CintaFosis18', 'divisi' => 'Kadiv Agama Sosial', 'password' => 'Rahmadani73Forista'],
        ['name' => 'Shafira Erwinda Putri', 'username' => 'ShafiraFosis19', 'divisi' => 'Agama Sosial', 'password' => 'Putri35Forista'],
        ['name' => 'Muhammad Taqiyuddin Azmi', 'username' => 'MuhammadFosis20', 'divisi' => 'Agama Sosial', 'password' => 'Azmi61Forista'],
        ['name' => 'Soffin Azka Widodo', 'username' => 'SoffinFosis21', 'divisi' => 'Agama Sosial', 'password' => 'Widodo94Forista'],
        ['name' => 'Salma Ghifarunisa Arsha Putri', 'username' => 'SalmaFosis22', 'divisi' => 'Agama Sosial', 'password' => 'Putri22Forista'],
        ['name' => 'Ahmad Sami', 'username' => 'AhmadFosis23', 'divisi' => 'Agama Sosial', 'password' => 'Sami57Forista'],
        ['name' => 'Monika Ruth Puteri Sinaga', 'username' => 'MonikaFosis24', 'divisi' => 'Kadiv Desain Media', 'password' => 'Sinaga18Forista'],
        ['name' => 'Elenna Myra Alexa', 'username' => 'ElennaFosis25', 'divisi' => 'Desain Media', 'password' => 'Alexa86Forista'],
        ['name' => 'Naisha Halimathul Zahra', 'username' => 'NaishaFosis26', 'divisi' => 'Desain Media', 'password' => 'Zahra43Forista'],
        ['name' => 'Akiva Dishan Ranu Wibowo', 'username' => 'AkivaFosis27', 'divisi' => 'Desain Media', 'password' => 'Wibowo29Forista'],
        ['name' => 'Anastasya Wening Prapaskah', 'username' => 'AnastasyaFosis28', 'divisi' => 'Desain Media', 'password' => 'Prapaskah67Forista'],
        ['name' => 'Rekyan Angga Raras Nur Kuswardhani', 'username' => 'RekyanFosis29', 'divisi' => 'Desain Media', 'password' => 'Kuswardhani12Forista'],
        ['name' => 'Devian Gentha Bhavana', 'username' => 'DevianFosis30', 'divisi' => 'Desain Media', 'password' => 'Bhavana54Forista'],
        ['name' => 'Muhammad Davin Rizky Renaldi', 'username' => 'MuhammadFosis31', 'divisi' => 'Desain Media', 'password' => 'Renaldi38Forista'],
        ['name' => 'Fransiska Cahya Kurniawan', 'username' => 'FransiskaFosis32', 'divisi' => 'Desain Media', 'password' => 'Kurniawan71Forista'],
        ['name' => 'Aldiano Anugrah Mayrendra Wijaya', 'username' => 'AldianoFosis33', 'divisi' => 'Kadiv Partnership', 'password' => 'Wijaya25Forista'],
        ['name' => 'Shalma Aldzira Hafid', 'username' => 'ShalmaFosis34', 'divisi' => 'Partnership', 'password' => 'Hafid89Forista'],
        ['name' => 'Fernanda Tribuana Prawiswara', 'username' => 'FernandaFosis35', 'divisi' => 'Partnership', 'password' => 'Prawiswara46Forista'],
        ['name' => 'Nakeysha Devira Khansa Salsabila', 'username' => 'NakeyshaFosis36', 'divisi' => 'Partnership', 'password' => 'Salsabila13Forista'],
        ['name' => 'Ailsa Syarifa Aimee', 'username' => 'AilsaFosis37', 'divisi' => 'Partnership', 'password' => 'Aimee62Forista'],
        ['name' => 'Zyvara Mayco Shaquilla Irawan', 'username' => 'ZyvaraFosis38', 'divisi' => 'Partnership', 'password' => 'Irawan34Forista'],
        ['name' => 'Dhania Rahmawati', 'username' => 'DhaniaFosis39', 'divisi' => 'Kadiv Usaha Dana', 'password' => 'Rahmawati78Forista'],
        ['name' => 'Lilian Leticia', 'username' => 'LilianFosis40', 'divisi' => 'Usaha Dana', 'password' => 'Leticia21Forista'],
        ['name' => 'Danish Ozora Putra Yudiansyah', 'username' => 'DanishFosis41', 'divisi' => 'Usaha Dana', 'password' => 'Yudiansyah56Forista'],
        ['name' => 'Muhammad Ziyahuda Fanani', 'username' => 'MuhammadFosis42', 'divisi' => 'Usaha Dana', 'password' => 'Fanani92Forista'],
        ['name' => 'Ayu Tegar Prameswari', 'username' => 'AyuFosis43', 'divisi' => 'Usaha Dana', 'password' => 'Prameswari47Forista'],
        ['name' => 'Muhammad Syarif Mufid Ramadhan', 'username' => 'MuhammadFosis44', 'divisi' => 'Usaha Dana', 'password' => 'Ramadhan15Forista'],
        ['name' => 'Josteve Aletheia Be Simeon', 'username' => 'JosteveFosis45', 'divisi' => 'Kadiv Humas', 'password' => 'Simeon83Forista'],
        ['name' => 'Naryama Raffi', 'username' => 'NaryamaFosis46', 'divisi' => 'Humas', 'password' => 'Raffi32Forista'],
        ['name' => 'Vrande Villa Jenio', 'username' => 'VrandeFosis47', 'divisi' => 'Humas', 'password' => 'Jenio69Forista'],
        ['name' => 'Syafiqah Airunnisa Haruko', 'username' => 'SyafiqahFosis48', 'divisi' => 'Humas', 'password' => 'Haruko24Forista'],
        ['name' => 'Yudha Aulia Nugraha', 'username' => 'YudhaFosis49', 'divisi' => 'Humas', 'password' => 'Nugraha51Forista'],
        ['name' => 'Diandra Ayu Nalani Putri', 'username' => 'DiandraFosis50', 'divisi' => 'Humas', 'password' => 'Putri87Forista'],
        ['name' => 'Ninda Rahmawati', 'username' => 'NindaFosis51', 'divisi' => 'Kadiv PSDM', 'password' => 'Rahmawati36Forista'],
        ['name' => 'Muhammad Akbar Ramadhani', 'username' => 'MuhammadFosis52', 'divisi' => 'PSDM', 'password' => 'Ramadhani74Forista'],
        ['name' => 'Thalita Bunga Diva Putri Krisnadi', 'username' => 'ThalitaFosis53', 'divisi' => 'PSDM', 'password' => 'Krisnadi41Forista'],
        ['name' => 'Wafi Setianto Putra', 'username' => 'WafiFosis54', 'divisi' => 'PSDM', 'password' => 'Putra93Forista'],
        ['name' => 'Erlyta Bulan Ramadhani', 'username' => 'ErlytaFosis55', 'divisi' => 'PSDM', 'password' => 'Ramadhani26Forista'],
        ['name' => 'Nabila Nahla Agustin', 'username' => 'NabilaFosis56', 'divisi' => 'Kadiv Kampanye', 'password' => 'Agustin68Forista'],
        ['name' => 'Ashsyifa Noor Aisha Cleverly Widodo', 'username' => 'AshsyifaFosis57', 'divisi' => 'Kampanye', 'password' => 'Widodo17Forista'],
        ['name' => 'Anwar Khoirul Fajri', 'username' => 'AnwarFosis58', 'divisi' => 'Kampanye', 'password' => 'Fajri59Forista'],
        ['name' => 'Danindra Hanif Saputro', 'username' => 'DanindraFosis59', 'divisi' => 'Kampanye', 'password' => 'Saputro44Forista'],
        ['name' => 'Ahmad Malik Mahrus', 'username' => 'AhmadFosis60', 'divisi' => 'Kampanye', 'password' => 'Mahrus81Forista'],
    ];

    public function run(): void
    {
        AppSetting::current();

        // 1. Buat 9 divisi FORISA dengan warna.
        $divisions = [];
        foreach (self::DIVISION_COLORS as $key => $color) {
            $divisions[$key] = Division::firstOrCreate(
                ['name' => $key],
                ['color' => $color]
            )->fresh();
        }

        // 2. Akun admin inti (divisi BPH).
        $adminPassword = env('SEEDER_ADMIN_PASSWORD') ?: Str::password(16);
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
                    'email' => strtolower($admin['username']) . '@forista.or.id',
                    'email_verified_at' => now(),
                ]
            );
        }

        // 3. Buat anggota dari data diekstrak (tidak perlu file Excel).
        $memberPassword = env('SEEDER_MEMBER_PASSWORD') ?: 'member2026';
        $created = 0;
        $skipped = 0;

        foreach (self::MEMBERS as $member) {
            $divisionKey = self::DIVISION_MAP[$member['divisi']] ?? 'BPH';
            $divisionId = $divisions[$divisionKey]->id;
            $email = strtolower($member['username']) . '@forista.or.id';

            User::firstOrCreate(
                ['username' => $member['username']],
                [
                    'name' => $member['name'],
                    'division_id' => $divisionId,
                    'password' => bcrypt($member['password'] ?: $memberPassword),
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
