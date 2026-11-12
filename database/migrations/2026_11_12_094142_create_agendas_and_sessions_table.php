<?php

use App\Enums\SessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location_name');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('radius_m')->default(20);
            $table->unsignedTinyInteger('weekday'); // 0 (Minggu) - 6 (Sabtu) sesuai Carbon::dayOfWeek
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('window_minutes')->default(15);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestampsTz();
        });

        Schema::create('agenda_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')
                ->constrained('agendas')
                ->cascadeOnDelete();
            $table->date('session_date');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->timestampTz('window_opens_at');
            $table->timestampTz('window_closes_at');
            $table->string('status')->default(SessionStatus::Scheduled->value)->index();

            // Penanda idempoten untuk scheduler pengingat
            $table->timestampTz('reminded_before_start_at')->nullable();
            $table->timestampTz('reminded_window_open_at')->nullable();
            $table->timestampTz('reminded_before_close_at')->nullable();

            $table->timestampsTz();

            $table->unique(['agenda_id', 'session_date']);
            $table->index(['status', 'window_opens_at', 'window_closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_sessions');
        Schema::dropIfExists('agendas');
    }
};
