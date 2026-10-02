<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_request_id')->constrained()->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('recipient');
            $table->json('summary');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['activity_request_id', 'event', 'recipient'], 'activity_email_event_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_email_deliveries');
    }
};
