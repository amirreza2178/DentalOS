<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->index();
            $table->text('clinical_summary');
            $table->text('operational_summary')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        Schema::create('treatment_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('required_qualification')->index();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->index();
            $table->text('completion_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['assignee_user_id', 'status']);
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->text('operational_note')->nullable()->after('status');
            $table->text('outcome_note')->nullable()->after('operational_note');
            $table->foreignId('patient_visit_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_visit_id');
            $table->dropColumn(['operational_note', 'outcome_note']);
        });

        Schema::dropIfExists('treatment_tasks');
        Schema::dropIfExists('treatment_plans');
    }
};
