<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gift_requests', function (Blueprint $table) {
            $table->dateTime('slot_start_datetime')->nullable()->after('pickup_slot_id');
            $table->dateTime('slot_end_datetime')->nullable()->after('slot_start_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_requests', function (Blueprint $table) {
            $table->dropColumn(['slot_start_datetime', 'slot_end_datetime']);
        });
    }
};
