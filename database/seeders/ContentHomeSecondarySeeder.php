<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContentHome;
use Illuminate\Support\Carbon;

class ContentHomeSecondarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'display_order' => 3,
                'title' => 'Conheça nossa cozinha',
                'text_body' => 'Veja como preparamos cada salgado com carinho desde 1987.',
                'type' => 'image',
                'layout' => 'split',
                'image_url' => 'https://example.com/images/cozinha.jpg',
            ],
            [
                'display_order' => 4,
                'title' => 'Programa de fidelidade',
                'text_body' => 'Junte Coinxinhas a cada compra e troque por recompensas exclusivas.',
                'type' => 'text',
                'layout' => 'card',
                'cta_label' => 'Ver recompensas',
                'cta_url' => 'app://loyalty',
                'background_color' => '#f0f6ff',
            ],
            [
                'display_order' => 5,
                'title' => null,
                'text_body' => null,
                'type' => 'only_image',
                'layout' => 'banner',
                'image_url' => 'https://example.com/images/vitrine.jpg',
                'cta_label' => null,
                'cta_url' => 'https://example.com/cardapio',
                'background_color' => null,
            ],
        ];

        foreach ($records as $record) {
            ContentHome::updateOrCreate(
                [
                    'title' => $record['title'],
                    'display_order' => $record['display_order'],
                ],
                array_merge(
                    $record,
                    [
                        'is_active' => true,
                        'publish_at' => Carbon::now(),
                    ]
                )
            );
        }
    }
}
