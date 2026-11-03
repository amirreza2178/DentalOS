<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('status')->index();
            $table->foreignId('checked_in_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('arrived_at')->nullable()->index();
            $table->dateTime('called_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'arrived_at']);
        });

        Schema::create('waiting_queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_visit_id')->constrained()->restrictOnDelete();
            $table->date('queue_date')->index();
            $table->unsignedInteger('position');
            $table->string('status')->index();
            $table->dateTime('arrived_at');
            $table->dateTime('called_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('called_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['queue_date', 'position']);
            $table->index(['queue_date', 'status', 'position'], 'queue_day_status_position_idx');
        });

        Schema::create('operational_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_events');
        Schema::dropIfExists('waiting_queue_entries');
        Schema::dropIfExists('patient_visits');
    }
};
