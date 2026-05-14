<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $category = Category::create(['name' => 'X', 'slug' => 'x', 'is_active' => true]);
        return Product::create([
            'category_id' => $category->id,
            'name' => 'P', 'slug' => 'p',
            'price' => 1000, 'stock_quantity' => 10,
            'gender' => 'unisex', 'is_active' => true,
        ]);
    }

    public function test_user_can_add_product_to_wishlist(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/wishlist/{$product->id}")
            ->assertSuccessful();

        $this->assertDatabaseHas('wishlists', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_cannot_add_same_product_twice(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'sanctum')->postJson("/api/wishlist/{$product->id}");
        $this->actingAs($user, 'sanctum')->postJson("/api/wishlist/{$product->id}");

        $this->assertEquals(1, Wishlist::where([
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ])->count());
    }

    public function test_user_can_remove_from_wishlist(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/wishlist/{$product->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('wishlists', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_wishlist(): void
    {
        $this->getJson('/api/wishlist')->assertStatus(401);
    }
}
