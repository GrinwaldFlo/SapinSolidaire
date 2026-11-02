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
        // Aggregates only: no timestamps, so a counter cannot be matched with the e-mail logs
        Schema::create('registration_funnel_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('season_id')->constrained()->cascadeOnDelete();
            $table->string('step', 50);
            $table->unsignedInteger('count')->default(0);

            $table->unique(['season_id', 'step']);
        });

        Schema::table('email_tokens', function (Blueprint $table) {
            $table->json('funnel_steps')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_tokens', function (Blueprint $table) {
            $table->dropColumn('funnel_steps');
        });

        Schema::dropIfExists('registration_funnel_counters');
    }
};
