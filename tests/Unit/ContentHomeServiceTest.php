<?php

namespace Tests\Unit;

use App\Models\ContentHome;
use App\Models\HomeComponent;
use App\Services\ContentHomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentHomeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_clashing_item_when_creating_new_content_home_entry(): void
    {
        Storage::fake('public');

        HomeComponent::create([
            'key' => 'PromoBanner',
            'label' => 'Promo Banner',
            'is_active' => true,
        ]);

        $existing = ContentHome::create([
            'title' => 'Antigo',
            'display_order' => 1,
            'type' => 'text',
            'layout' => 'default',
            'is_active' => true,
        ]);

        /** @var ContentHomeService $service */
        $service = $this->app->make(ContentHomeService::class);

        $created = $service->create([
            'title' => 'Novo',
            'display_order' => 1,
            'type' => 'component',
            'layout' => 'hero',
            'component_name' => 'PromoBanner',
            'component_props' => '{"cta":"ativar"}',
            'is_active' => true,
            'show_component_title' => true,
            'cta_image_only' => false,
        ], UploadedFile::fake()->create('hero.jpg', 64, 'image/jpeg'));

        $this->assertSame(1, $created->display_order);
        $this->assertSame(['cta' => 'ativar'], $created->component_props);
        $this->assertSame(2, $existing->fresh()->display_order);
        $this->assertStringStartsWith('/storage/content-home/', (string) $created->image_url);
    }

    public function test_it_swaps_display_order_on_update(): void
    {
        $first = ContentHome::create([
            'title' => 'Primeiro',
            'display_order' => 1,
            'type' => 'text',
            'layout' => 'default',
            'is_active' => true,
        ]);
        $second = ContentHome::create([
            'title' => 'Segundo',
            'display_order' => 2,
            'type' => 'text',
            'layout' => 'default',
            'is_active' => true,
        ]);

        $service = $this->app->make(ContentHomeService::class);
        $service->update($second, [
            'title' => 'Segundo atualizado',
            'display_order' => 1,
            'type' => 'text',
            'layout' => 'default',
            'is_active' => true,
            'show_component_title' => false,
            'cta_image_only' => false,
        ]);

        $this->assertSame(2, $first->fresh()->display_order);
        $this->assertSame(1, $second->fresh()->display_order);
    }
}
