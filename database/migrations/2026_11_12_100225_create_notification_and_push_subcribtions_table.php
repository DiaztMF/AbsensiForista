<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('endpoint')->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->default('aesgcm');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('notifications');
    }
};
