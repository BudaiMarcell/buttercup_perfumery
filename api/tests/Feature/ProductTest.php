<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_returns_active_products(): void
    {
        $category = Category::create([
            'name' => 'Teszt', 'slug' => 'teszt', 'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Aktív', 'slug' => 'aktiv',
            'price' => 1000, 'stock_quantity' => 5,
            'gender' => 'unisex', 'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Rejtett', 'slug' => 'rejtett',
            'price' => 1000, 'stock_quantity' => 5,
            'gender' => 'unisex', 'is_active' => false,
        ]);

        $response = $this->getJson('/api/products')->assertOk();

        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertContains('aktiv', $slugs);
        $this->assertNotContains('rejtett', $slugs);
    }

    public function test_product_show_returns_single_product_by_slug(): void
    {
        $category = Category::create(['name' => 'X', 'slug' => 'x', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Bleu', 'slug' => 'bleu-de-test',
            'price' => 45990, 'stock_quantity' => 10,
            'gender' => 'male', 'is_active' => true,
        ]);

        $this->getJson('/api/products/bleu-de-test')
            ->assertOk()
            ->assertJsonPath('data.slug', 'bleu-de-test')
            ->assertJsonPath('data.name', 'Bleu');
    }

    public function test_product_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/products/nem-letezo-termek')
            ->assertStatus(404);
    }
}
