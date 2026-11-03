<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_locks', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['resource_type', 'resource_id'], 'reservation_lock_resource_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_locks');
    }
};
