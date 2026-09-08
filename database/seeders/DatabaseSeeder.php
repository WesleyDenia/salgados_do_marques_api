<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 👤 Admin
        User::firstOrCreate(
            ['email' => 'admin@salgados.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
                'theme' => 'light',
            ]
        );

        // 🍴 Categorias
        $fritos = Category::firstOrCreate(['name' => 'Fritos'], [
            'description' => 'Salgados prontos (fritos)',
        ]);

        $congelados = Category::firstOrCreate(['name' => 'Congelados'], [
            'description' => 'Para fritar em casa',
        ]);

        // 🥟 Produtos
        Product::firstOrCreate(
            ['name' => 'Coxinha 70g Frango'],
            ['category_id' => $fritos->id, 'price' => 0.79, 'active' => true]
        );

        Product::firstOrCreate(
            ['name' => 'Coxinha 70g Bacalhau'],
            ['category_id' => $fritos->id, 'price' => 0.79, 'active' => true]
        );

        Product::firstOrCreate(
            ['name' => 'Pack 25 Minis Fritos'],
            ['category_id' => $fritos->id, 'price' => 9.00, 'active' => true]
        );

        Product::firstOrCreate(
            ['name' => 'Pack 25 Minis Congelados'],
            ['category_id' => $congelados->id, 'price' => 7.50, 'active' => true]
        );

        // 🎉 Promoções
        Promotion::firstOrCreate(
            ['code' => 'BEMVINDO'],
            [
                'title' => 'Bem-vindo ao App!',
                'body' => 'Ganhe 10% de desconto na sua primeira compra!',
                'discount_percent' => 10,
                'image' => 'https://picsum.photos/seed/bemvindo/800/400',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'is_global' => true,
                'active' => true,
            ]
        );

        Promotion::firstOrCreate(
            ['code' => 'FESTA35'],
            [
                'title' => 'Pack Festa com 35% OFF',
                'body' => 'Aproveite o desconto de 35% nos packs de 100 minis salgados!',
                'discount_percent' => 35,
                'image' => 'https://picsum.photos/seed/festa/800/400',
                'starts_at' => now(),
                'ends_at' => now()->addWeeks(3),
                'is_global' => true,
                'active' => true,
            ]
        );

        // 🎟️ Cupom
        Coupon::firstOrCreate(
            ['code' => 'VIP-CLIENTE'],
            [
                'title' => 'Cliente VIP',
                'body' => 'Cupom especial cliente',
                'recurrence' => 'none',
                'image_url' => 'https://picsum.photos/seed/festa/800/400',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'active' => true,
                'type' => 'percent',
                'amount' => 10,
            ]
        );

        $this->call([
            SettingSeeder::class,
            ContentHomeSeeder::class,
            ContentHomeSecondarySeeder::class,
            UrbanCampaignSeeder::class,
        ]);
    }
}
