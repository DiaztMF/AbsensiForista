<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestampsTz();
        });

        // Tabel users dibuat oleh migrasi 0001; di sini hanya ditambah kolom domain FORISTA.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->foreignId('division_id')
                ->nullable()
                ->after('id')
                ->constrained('divisions')
                ->restrictOnDelete(); // Blokir penghapusan divisi jika masih ada anggota
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default(UserRole::Member->value)->index();
            $table->boolean('is_active')->default(true)->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'is_active']);
            $table->string('email')->nullable(false)->change();
        });

        Schema::dropIfExists('divisions');
    }
};
