<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->timestamp('excluded_at')->nullable();
            $table->text('exclusion_reason')->nullable();
        });

        // Backfill: families with a final rejection in any season are excluded.
        $rejectedRequests = DB::table('gift_requests')
            ->where('status', 'rejected_final')
            ->orderBy('status_changed_at')
            ->get(['family_id', 'rejection_comment', 'status_changed_at', 'updated_at']);

        foreach ($rejectedRequests as $request) {
            DB::table('families')->where('id', $request->family_id)->update([
                'excluded_at' => $request->status_changed_at ?? $request->updated_at ?? now(),
                'exclusion_reason' => $request->rejection_comment,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['excluded_at', 'exclusion_reason']);
        });
    }
};
