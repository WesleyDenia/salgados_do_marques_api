<?php

namespace Database\Seeders;

use App\Models\QrCode;
use App\Models\Question;
use App\Models\QuestionResponse;
use Illuminate\Database\Seeder;

class UrbanCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            'kibe' => [
                'qr_code' => 'QRXPTO',
                'secret_number' => 1,
                'collection_label' => 'Kibe',
                'eyebrow' => 'Uma pista de forma comprida',
                'riddle' => [
                    'Por fora sou dourado e estaladiço.',
                    'Por dentro, carne bem temperada.',
                    'Tenho raízes no Médio Oriente e desapareço num instante.',
                    'Quem sou eu?',
                ],
                'options' => [
                    ['response' => 'Kibe', 'is_correct' => true],
                    ['response' => 'Coxinha de Frango', 'is_correct' => false],
                    ['response' => 'Bolinha de Queijo', 'is_correct' => false],
                    ['response' => 'Travesseirinho de Carne', 'is_correct' => false],
                ],
                'reward_when_correct' => 10,
                'reward_when_wrong' => 5,
            ],
            'carne' => [
                'qr_code' => 'QR-CARNE',
                'secret_number' => 2,
                'collection_label' => 'Travesseirinho de Carne',
                'eyebrow' => 'Uma pista macia por dentro',
                'riddle' => [
                    'Tenho nome de coisa que ajuda a descansar.',
                    'Mas ninguém me leva para a cama.',
                    'Sou pequeno, recheado de carne e feito para partilhar.',
                    'Quem sou eu?',
                ],
                'options' => [
                    ['response' => 'Travesseirinho de Carne', 'is_correct' => true],
                    ['response' => 'Enroladinho de Salsicha', 'is_correct' => false],
                    ['response' => 'Kibe', 'is_correct' => false],
                    ['response' => 'Bolinha de Queijo', 'is_correct' => false],
                ],
                'reward_when_correct' => 15,
                'reward_when_wrong' => 10,
            ],
            'salsicha' => [
                'qr_code' => 'QR-SALSICHA',
                'secret_number' => 3,
                'collection_label' => 'Enroladinho de Salsicha',
                'eyebrow' => 'Uma pista bem enrolada',
                'riddle' => [
                    'Levo o recheio escondido num abraço dourado.',
                    'Sou comprido, divertido e desapareço antes da festa começar.',
                    'Quem sou eu?',
                ],
                'options' => [
                    ['response' => 'Bolinha de Queijo', 'is_correct' => false],
                    ['response' => 'Enroladinho de Salsicha', 'is_correct' => true],
                    ['response' => 'Coxinha de Frango', 'is_correct' => false],
                    ['response' => 'Travesseirinho de Carne', 'is_correct' => false],
                ],
                'reward_when_correct' => 20,
                'reward_when_wrong' => 10,
            ],
            'queijo' => [
                'qr_code' => 'QR-QUEIJO',
                'secret_number' => 4,
                'collection_label' => 'Bolinha de Queijo',
                'eyebrow' => 'Uma pista redonda e irresistível',
                'riddle' => [
                    'Sou pequena, redonda e dourada.',
                    'Quando me abrem, o meu coração pode esticar.',
                    'Quem sou eu?',
                ],
                'options' => [
                    ['response' => 'Kibe', 'is_correct' => false],
                    ['response' => 'Travesseirinho de Carne', 'is_correct' => false],
                    ['response' => 'Bolinha de Queijo', 'is_correct' => true],
                    ['response' => 'Enroladinho de Salsicha', 'is_correct' => false],
                ],
                'reward_when_correct' => 25,
                'reward_when_wrong' => 15,
            ],
            'coxinha' => [
                'qr_code' => 'QR-LENDARIO',
                'secret_number' => 5,
                'collection_label' => 'Coxinha de Frango',
                'eyebrow' => 'Talvez tenhas encontrado o lendário',
                'riddle' => [
                    'Tenho uma armadura dourada,',
                    'um coração cremoso',
                    'e desapareço rapidamente quando chego à mesa.',
                    'Quem sou eu?',
                ],
                'options' => [
                    ['response' => 'Coxinha de Frango', 'is_correct' => true],
                    ['response' => 'Kibe', 'is_correct' => false],
                    ['response' => 'Bolinha de Queijo', 'is_correct' => false],
                    ['response' => 'Travesseirinho de Carne', 'is_correct' => false],
                ],
                'reward_when_correct' => 50,
                'reward_when_wrong' => 25,
            ],
        ];

        foreach ($challenges as $type => $challenge) {
            QrCode::updateOrCreate(
                ['code' => $challenge['qr_code']],
                ['type' => $type, 'active' => true]
            );

            $question = Question::updateOrCreate(
                ['code_type' => $type, 'secret_number' => $challenge['secret_number']],
                [
                    'question' => implode("\n", $challenge['riddle']),
                    'collection_label' => $challenge['collection_label'],
                    'eyebrow' => $challenge['eyebrow'],
                    'reward_when_correct' => $challenge['reward_when_correct'],
                    'reward_when_wrong' => $challenge['reward_when_wrong'],
                    'active' => true,
                    'display_order' => $challenge['secret_number'],
                ]
            );

            foreach ($challenge['options'] as $index => $option) {
                QuestionResponse::updateOrCreate(
                    ['question_id' => $question->id, 'response' => $option['response']],
                    [
                        'is_correct' => $option['is_correct'],
                        'display_order' => $index + 1,
                    ]
                );
            }
        }
    }
}
