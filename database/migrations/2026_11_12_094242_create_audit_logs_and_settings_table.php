<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('target_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('action'); // 'manual_attendance' | 'password_reset'
            $table->text('reason');
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['action', 'created_at']);
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('default_radius_m')->default(20);
            $table->unsignedSmallInteger('default_window_minutes')->default(15);
            $table->unsignedSmallInteger('gps_accuracy_ceiling_m')->default(20);
            $table->unsignedSmallInteger('remind_before_start_minutes')->default(30);
            $table->boolean('remind_on_window_open')->default(true);
            $table->unsignedSmallInteger('remind_before_close_minutes')->default(5);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('audit_logs');
    }
};
