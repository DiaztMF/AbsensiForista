<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::current();

        $bph = Division::firstOrCreate(['name' => 'Badan Pengurus Harian']);
        $humas = Division::firstOrCreate(['name' => 'Hubungan Masyarakat']);
        $kominfo = Division::firstOrCreate(['name' => 'Komunikasi & Informasi']);
        $kaderisasi = Division::firstOrCreate(['name' => 'Kaderisasi & Organisasi']);

        // 3 Admin (juga berperan sebagai anggota aktif)
        $admins = [
            ['name' => 'Admin Ketua FORISTA', 'username' => 'admin.ketua', 'division_id' => $bph->id],
            ['name' => 'Admin Sekretaris FORISTA', 'username' => 'admin.sekretaris', 'division_id' => $bph->id],
            ['name' => 'Admin Bendahara FORISTA', 'username' => 'admin.bendahara', 'division_id' => $bph->id],
        ];

        foreach ($admins as $admin) {
            User::firstOrCreate(
                ['username' => $admin['username']],
                [
                    'name' => $admin['name'],
                    'division_id' => $admin['division_id'],
                    'password' => 'forista2026',
                    'role' => UserRole::Admin,
                    'is_active' => true,
                ]
            );
        }

        // Sampel Anggota untuk pengujian awal
        User::firstOrCreate(
            ['username' => 'anggota.satu'],
            [
                'name' => 'Bima Pratama',
                'division_id' => $kominfo->id,
                'password' => 'member2026',
                'role' => UserRole::Member,
                'is_active' => true,
            ]
        );
    }
}