<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->string('progress')->default('pending')->index();
            $table->text('tracker_remarks')->nullable();
            $table->unsignedInteger('expected_participants')->nullable();
        });

        DB::table('activity_requests')->update(['progress' => 'needs_confirmation']);
        DB::table('participants')->select('activity_request_id')->selectRaw('COUNT(*) AS total')
            ->groupBy('activity_request_id')->orderBy('activity_request_id')->get()
            ->each(fn ($row) => DB::table('activity_requests')->where('id', $row->activity_request_id)
                ->update(['expected_participants' => $row->total]));
    }

    public function down(): void
    {
        Schema::table('activity_requests', fn (Blueprint $table) => $table->dropIndex(['progress']));
        Schema::table('activity_requests', fn (Blueprint $table) => $table->dropColumn([
            'progress', 'tracker_remarks', 'expected_participants',
        ]));
    }
};
