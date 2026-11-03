<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_services', function (Blueprint $table) {
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->boolean('requires_specialist')->default(false);
            $table->unsignedTinyInteger('required_clinical_staff_count')->default(0);
            $table->boolean('requires_chair')->default(true);
            $table->json('required_chair_capabilities')->nullable();
            $table->string('booking_confirmation_mode')->nullable();
            $table->boolean('is_bookable_online')->default(false);
        });

        Schema::table('chairs', function (Blueprint $table) {
            $table->json('capabilities')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('treatment_services', function (Blueprint $table) {
            $table->dropColumn([
                'buffer_minutes',
                'requires_specialist',
                'required_clinical_staff_count',
                'requires_chair',
                'required_chair_capabilities',
                'booking_confirmation_mode',
                'is_bookable_online',
            ]);
        });

        Schema::table('chairs', function (Blueprint $table) {
            $table->dropColumn('capabilities');
        });
    }
};
