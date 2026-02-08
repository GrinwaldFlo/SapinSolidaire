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
        Schema::table('seasons', function (Blueprint $table) {
            $table->unsignedInteger('family_limit_per_slot')->nullable()->after('pickup_address');
            $table->unsignedInteger('slot_duration_minutes')->nullable()->after('family_limit_per_slot');
            $table->string('responsible_name')->nullable()->after('slot_duration_minutes');
            $table->string('responsible_phone', 20)->nullable()->after('responsible_name');
            $table->string('responsible_email')->nullable()->after('responsible_phone');
        });

        Schema::create('pickup_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->timestamps();

            $table->index('season_id');
        });

        Schema::table('gift_requests', function (Blueprint $table) {
            $table->foreignId('pickup_slot_id')->nullable()->after('rejection_comment')->constrained('pickup_slots')->nullOnDelete();
            $table->index('pickup_slot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_requests', function (Blueprint $table) {
            $table->dropForeign(['pickup_slot_id']);
            $table->dropColumn('pickup_slot_id');
        });

        Schema::dropIfExists('pickup_slots');

        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn([
                'family_limit_per_slot',
                'slot_duration_minutes',
                'responsible_name',
                'responsible_phone',
                'responsible_email',
            ]);
        });
    }
};
