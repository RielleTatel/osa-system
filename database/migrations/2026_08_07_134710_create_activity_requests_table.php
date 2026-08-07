<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users');
            $table->string('activity_type'); // in_campus | off_campus
            $table->string('title');
            $table->string('nature_of_activity');
            $table->string('nature_of_engagement'); // organizer | partner | participant
            $table->string('main_organizer')->nullable();
            $table->date('date_start');
            $table->date('date_end');
            $table->time('time_of_activity');
            $table->string('venue');
            $table->text('purpose');
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_requests');
    }
};
