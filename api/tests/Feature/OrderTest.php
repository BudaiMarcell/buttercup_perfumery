<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function setupUserWithProduct(int $stock = 10): array
    {
        $user = User::factory()->create();

        $address = Address::create([
            'user_id'    => $user->id,
            'label'      => 'Otthon',
            'country'    => 'Magyarország',
            'city'       => 'Budapest',
            'zip_code'   => '1011',
            'street'     => 'Fő utca 12.',
            'is_default' => true,
        ]);

        $category = Category::create(['name' => 'X', 'slug' => 'x', 'is_active' => true]);

        $product = Product::create([
            'category_id'    => $category->id,
            'name'           => 'P',
            'slug'           => 'p',
            'price'          => 5000,
            'stock_quantity' => $stock,
            'gender'         => 'unisex',
            'is_active'      => true,
        ]);

        return compact('user', 'address', 'product');
    }

    public function test_user_can_place_order(): void
    {
        Mail::fake();
        ['user' => $user, 'address' => $address, 'product' => $product] = $this->setupUserWithProduct();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'address_id'     => $address->id,
                'payment_method' => 'card',
                'items'          => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.total_amount', '10000.00');

        $this->assertDatabaseHas('orders', [
            'user_id'      => $user->id,
            'total_amount' => '10000.00',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity'   => 2,
            'subtotal'   => '10000.00',
        ]);

        $product->refresh();
        $this->assertEquals(8, $product->stock_quantity);
    }

    public function test_order_fails_when_stock_insufficient(): void
    {
        Mail::fake();
        ['user' => $user, 'address' => $address, 'product' => $product] = $this->setupUserWithProduct(stock: 1);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'address_id'     => $address->id,
                'payment_method' => 'card',
                'items'          => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
        $product->refresh();
        $this->assertEquals(1, $product->stock_quantity);
    }

    public function test_user_can_only_see_own_orders(): void
    {
        ['user' => $user1, 'address' => $address] = $this->setupUserWithProduct();
        $user2 = User::factory()->create();

        Order::create([
            'user_id'        => $user1->id,
            'address_id'     => $address->id,
            'status'         => 'pending',
            'total_amount'   => 1000,
            'payment_method' => 'card',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($user2, 'sanctum')->getJson('/api/orders');
        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
