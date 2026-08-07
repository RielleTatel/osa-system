<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osa_form3s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('program_name');
            $table->string('course');
            $table->string('destination_venue');
            $table->string('inclusive_dates');
            $table->integer('number_of_students');
            $table->text('personnel_in_charge');
            $table->string('moderator_approval_status')->default('pending'); // pending | approved | rejected
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderator_approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osa_form3s');
    }
};
