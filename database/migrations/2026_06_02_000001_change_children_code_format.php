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
            $table->unsignedInteger('next_family_number')->default(1)->after('responsible_email');
        });

        Schema::table('gift_requests', function (Blueprint $table) {
            $table->unsignedInteger('family_number')->nullable()->after('season_id');
        });

        Schema::table('children', function (Blueprint $table) {
            $table->unsignedInteger('child_number')->nullable()->after('shoe_size');
        });

        // Change code column: larger size, nullable, remove unique
        Schema::table('children', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->change();
        });

        // Drop the unique index on code and re-add as a regular index
        Schema::table('children', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->char('code', 4)->nullable(false)->unique()->change();
            $table->dropColumn('child_number');
        });

        Schema::table('gift_requests', function (Blueprint $table) {
            $table->dropColumn('family_number');
        });

        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn('next_family_number');
        });
    }
};
