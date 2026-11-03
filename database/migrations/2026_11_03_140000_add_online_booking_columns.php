<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('identity_status')->default('verified')->after('status');
            $table->index('identity_status');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->uuid('public_reference')->nullable()->unique()->after('id');
            $table->text('decision_reason')->nullable()->after('patient_notes');
            $table->timestamp('decided_at')->nullable()->after('decision_reason');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['public_reference', 'decision_reason', 'decided_at']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['identity_status']);
            $table->dropColumn('identity_status');
        });
    }
};
