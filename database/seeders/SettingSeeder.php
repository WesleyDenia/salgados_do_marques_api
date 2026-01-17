<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'LOYALTY_POINTS_PER_EURO', 'value' => '10', 'type' => 'integer'],
            ['key' => 'LOYALTY_MAX_POINTS_PER_SALE', 'value' => '500', 'type' => 'integer'],
            ['key' => 'LOYALTY_SOURCE', 'value' => 'vendus', 'type' => 'string'],
            [
                'key' => 'ASSET_BASE_URL',
                'value' => config('app.url'),
                'type' => 'string',
                'editable' => true,
            ],
            [
                'key' => 'LGPD_TERMS',
                'value' => 'Ao prosseguir com o cadastro você declara ter lido e aceitado o Termo de Consentimento LGPD da Salgados do Marquês.',
                'type' => 'string',
                'editable' => true,
            ],
                    [
                'key' => 'order_start_time',
                'value' => '12:00',
                'type' => 'string',
                'editable' => true,
            ],
            [
                'key' => 'order_end_time',
                'value' => '20:00',
                'type' => 'string',
                'editable' => true,
            ],
            [
                'key' => 'order_minimum_minutes',
                'value' => '30',
                'type' => 'integer',
                'editable' => true,
            ],
            [
                'key' => 'order_cancel_minutes',
                'value' => '60',
                'type' => 'integer',
                'editable' => true,
            ],
            [
                'key' => 'order_timezone',
                'value' => 'Europe/Lisbon',
                'type' => 'string',
                'editable' => true,
            ],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
