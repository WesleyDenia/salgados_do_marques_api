<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContentHome;
use Illuminate\Support\Carbon;

class ContentHomeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'display_order' => 1,
                'title' => 'Bem-vindo à Salgados do Marquês',
                'text_body' => 'Descubra os sabores que conquistaram o bairro com receitas artesanais e ingredientes selecionados.',
                'type' => 'text',
                'layout' => 'highlight',
                'background_color' => '#fff8f0',
                'cta_label' => 'Ver Cardápio',
                'cta_url' => 'app://menu',
            ],
            [
                'display_order' => 2,
                'title' => 'Promoções da Semana',
                'text_body' => 'Aproveite ofertas especiais em combos e kits para toda a família.',
                'type' => 'image',
                'layout' => 'banner',
                'image_url' => 'https://example.com/images/promo-semana.png',
                'cta_label' => 'Ver Promoções',
                'cta_url' => 'app://promotions',
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
