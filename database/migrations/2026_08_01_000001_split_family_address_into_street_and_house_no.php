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
            $table->string('street_name')->nullable()->after('last_name');
            $table->string('house_no', 20)->nullable()->after('street_name');
        });

        DB::table('families')
            ->select(['id', 'address'])
            ->orderBy('id')
            ->cursor()
            ->each(function ($family): void {
                if (! $family->address) {
                    return;
                }

                $address = trim($family->address);
                $streetName = $address;
                $houseNo = null;

                if (preg_match('/^(.*?)[,\s]+(\d+[\pL\d\-\/]*)$/u', $address, $matches)) {
                    $streetName = trim($matches[1]);
                    $houseNo = trim($matches[2]);
                }

                DB::table('families')
                    ->where('id', $family->id)
                    ->update([
                        'street_name' => $streetName,
                        'house_no' => $houseNo,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['street_name', 'house_no']);
        });
    }
};
