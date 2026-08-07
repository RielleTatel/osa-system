<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->string('final_scan_path')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->dropColumn('final_scan_path');
        });
    }
};
