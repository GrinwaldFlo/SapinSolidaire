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
            Setting::MAX_CONSECUTIVE_YEARS => '3',
            Setting::GIFT_SUGGESTIONS => "Jouet\nVêtement\nLivre\nJeu de société\nMatériel scolaire\nChaussures",
            Setting::GIFT_RESTRICTIONS => '',
            Setting::GIFTS_WITH_SHOE_SIZE => "chaussure\nbasket\nbotte\nsandale\nsoulier\nsneaker\nroller\npatin",
            Setting::GIFTS_WITH_SIZE => "veste\nmanteau\npantalon\njean\npull\nt-shirt\nvelo\ntrottinette",
            Setting::INTRODUCTION_TEXT => "Bienvenue sur Sapin Solidaire.\n\nCette plateforme vous permet de faire une demande de cadeau pour vos enfants.",
            Setting::REPLY_TO_EMAIL => '',
            Setting::CODE_PREFIX => 'Y',
            Setting::CODE_FAMILY_PADDING => '4',
            Setting::FAMILY_FORM_MULTI_STEP_ENABLED => '0',
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
