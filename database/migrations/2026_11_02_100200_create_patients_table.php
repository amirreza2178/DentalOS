<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id')->nullable()->unique();
            $table->string('mobile')->nullable();
            $table->string('mobile_normalized')->nullable()->index();
            $table->string('alt_phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('active')->index();
            $table->text('notes_admin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
