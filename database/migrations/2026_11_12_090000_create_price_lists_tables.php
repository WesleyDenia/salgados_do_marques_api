<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->integer('priority')->default(0)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('price_list_order_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['price_list_id', 'order_tag_id']);
        });

        Schema::create('price_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['price_list_id', 'product_id']);
            $table->unique(['price_list_id', 'product_variant_id']);
        });

        $now = now();
        $priceListId = DB::table('price_lists')->insertGetId([
            'name' => 'Normal',
            'priority' => 0,
            'active' => true,
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('products')->orderBy('id')->eachById(function (object $product) use ($now, $priceListId): void {
            DB::table('price_entries')->insert([
                'price_list_id' => $priceListId,
                'product_id' => $product->id,
                'product_variant_id' => null,
                'amount' => $product->price,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        DB::table('product_variants')->orderBy('id')->eachById(function (object $variant) use ($now, $priceListId): void {
            DB::table('price_entries')->insert([
                'price_list_id' => $priceListId,
                'product_id' => null,
                'product_variant_id' => $variant->id,
                'amount' => $variant->price,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_entries');
        Schema::dropIfExists('price_list_order_tag');
        Schema::dropIfExists('price_lists');
    }
};
