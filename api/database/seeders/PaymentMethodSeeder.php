<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereNotIn('email', ['admin@parfumeria.hu'])->take(2)->get();

        if ($users->isEmpty()) {
            return;
        }

        $cards = [
            [
                'brand'      => 'Visa',
                'last_four'  => '4242',
                'exp_month'  => 12,
                'exp_year'   => now()->year + 2,
                'is_default' => true,
            ],
            [
                'brand'      => 'Mastercard',
                'last_four'  => '5555',
                'exp_month'  => 6,
                'exp_year'   => now()->year + 3,
                'is_default' => true,
            ],
        ];

        foreach ($users as $i => $user) {
            $card = $cards[$i % count($cards)];
            PaymentMethod::firstOrCreate(
                [
                    'user_id'   => $user->id,
                    'last_four' => $card['last_four'],
                    'exp_month' => $card['exp_month'],
                    'exp_year'  => $card['exp_year'],
                ],
                [
                    'brand'      => $card['brand'],
                    'is_default' => $card['is_default'],
                ]
            );
        }
    }
}
