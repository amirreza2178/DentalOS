<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('treatment_service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('proposed_window_start');
            $table->date('proposed_window_end');
            $table->string('status')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('patient_confirmed_at')->nullable();
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('converted_appointment_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('treatment_service_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status');
            $table->string('source');
            $table->string('confirmation_mode')->nullable();
            $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('follow_up_id')->nullable()->constrained()->nullOnDelete();
            $table->text('patient_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
            $table->index(['patient_id', 'starts_at']);
        });

        Schema::create('appointment_resource_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['resource_type', 'resource_id', 'starts_at', 'ends_at'], 'allocation_resource_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_resource_allocations');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('follow_ups');
    }
};
