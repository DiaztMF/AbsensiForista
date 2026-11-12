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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')
                ->constrained('divisions')
                ->restrictOnDelete(); // Blokir penghapusan divisi jika masih ada anggota
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role')->default(UserRole::Member->value)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->rememberToken();
            $table->timestampsTz();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('divisions');
    }
};