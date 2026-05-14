<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;

class WishlistSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereNotIn('email', ['admin@parfumeria.hu'])->take(3)->get();
        $products = Product::take(6)->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($users as $i => $user) {
            $picks = $products->slice($i, 2);
            foreach ($picks as $product) {
                Wishlist::firstOrCreate([
                    'user_id'    => $user->id,
                    'product_id' => $product->id,
                ]);
            }
        }
    }
}
