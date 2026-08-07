<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('reference_code')->unique();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_slips');
    }
};
