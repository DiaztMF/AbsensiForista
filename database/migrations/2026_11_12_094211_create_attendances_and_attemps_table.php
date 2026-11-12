<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_session_id')
                ->constrained('agenda_sessions')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('status')->default('hadir');
            $table->timestampTz('checked_at');
            $table->decimal('distance_m', 10, 2)->nullable(); // Null jika input manual
            $table->string('source'); // 'auto' | 'manual'
            $table->text('manual_reason')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();

            // Jaminan 0 duplikasi meskipun 15 anggota check-in bersamaan
            $table->unique(['agenda_session_id', 'user_id']);
        });

        Schema::create('attendance_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_session_id')
                ->constrained('agenda_sessions')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // Koordinat mentah user TIDAK disimpan sesuai aturan privasi PRD
            $table->decimal('distance_m', 10, 2)->nullable();
            $table->decimal('accuracy_m', 8, 2);
            $table->string('result'); // 'matched' | 'outside_radius' | 'poor_accuracy'
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['agenda_session_id', 'user_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_attempts');
        Schema::dropIfExists('attendances');
    }
};