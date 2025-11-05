<?php

// database/seeders/LoyaltyRewardSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoyaltyReward;

class LoyaltyRewardSeeder extends Seeder {
    public function run(): void {
        LoyaltyReward::firstOrCreate(['threshold'=>1000],[
            'name'=>'Meta 1000',
            'description'=>'Meta padrão para desbloquear recompensa',
            'value' => 5.00,
            'active'=>true,
        ]);
    }
}
