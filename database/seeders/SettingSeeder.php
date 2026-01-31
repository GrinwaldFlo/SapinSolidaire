<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            Setting::SITE_NAME => 'Sapin Solidaire',
            Setting::ALLOWED_POSTAL_CODES => '',
            Setting::MAX_CONSECUTIVE_YEARS => '3',
            Setting::GIFT_SUGGESTIONS => "Jouet\nVêtement\nLivre\nJeu de société\nMatériel scolaire\nChaussures",
            Setting::INTRODUCTION_TEXT => "Bienvenue sur Sapin Solidaire.\n\nCette plateforme vous permet de faire une demande de cadeau pour vos enfants.",
            Setting::REPLY_TO_EMAIL => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
