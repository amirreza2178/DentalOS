<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['weekday', 'is_active']);
        });

        Schema::create('shift_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_shift_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });

        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('schedule_date')->nullable()->index();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->boolean('is_unavailable')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'schedule_date']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('title');
            $table->boolean('is_closed')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('staff_schedules');
        Schema::dropIfExists('shift_breaks');
        Schema::dropIfExists('work_shifts');
    }
};
