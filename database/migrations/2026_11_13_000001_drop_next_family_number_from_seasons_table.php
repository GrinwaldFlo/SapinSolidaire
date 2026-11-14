<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Family numbers are now given at print time as the smallest free number of the season
 * (gaps filled), so the season counter is no longer used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn('next_family_number');
        });
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->unsignedInteger('next_family_number')->default(1)->after('responsible_email');
        });
    }
};
