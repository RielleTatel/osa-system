<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osa_form3_compliance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osa_form3_id')->constrained('osa_form3s')->cascadeOnDelete();
            $table->string('activity_label');
            $table->boolean('compliance');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osa_form3_compliance_items');
    }
};
