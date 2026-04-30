<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('OWNER_EMAIL', 'owner@tracking.local')],
            [
                'name'     => 'Owner',
                'password' => Hash::make(env('OWNER_PASSWORD', 'ChangeMe!2026')),
                'role'     => User::ROLE_OWNER,
            ]
        );

        $demo = [
            ['name' => 'Herbal Cure Capsule 60ct',  'description' => 'Demo product A',  'rate' => 499.00, 'stock' => 500],
            ['name' => 'Herbal Cure Syrup 200ml',   'description' => 'Demo product B',  'rate' => 299.00, 'stock' => 800],
            ['name' => 'Immune Boost Tablet 30ct',  'description' => 'Demo product C',  'rate' => 349.00, 'stock' => 600],
            ['name' => 'Detox Powder 100g',         'description' => 'Demo product D',  'rate' => 599.00, 'stock' => 300],
            ['name' => 'Joint Care Oil 50ml',       'description' => 'Demo product E',  'rate' => 249.00, 'stock' => 400],
            ['name' => 'Hair Growth Serum 60ml',    'description' => 'Demo product F',  'rate' => 799.00, 'stock' => 250],
        ];

        foreach ($demo as $row) {
            Product::firstOrCreate(['name' => $row['name']], $row);
        }

        $dealer = User::firstOrCreate(
            ['email' => 'dealer@demo.local'],
            [
                'name'     => 'Demo Dealer',
                'password' => Hash::make('DealerDemo!1'),
                'role'     => User::ROLE_DEALER,
                'phone'    => '+91-9000000001',
            ]
        );

        User::firstOrCreate(
            ['email' => 'client@demo.local'],
            [
                'name'       => 'Demo Client',
                'password'   => Hash::make('ClientDemo!1'),
                'role'       => User::ROLE_CLIENT,
                'phone'      => '+91-9000000002',
                'address'    => '221B Baker Street, Kolkata',
                'created_by' => $dealer->id,
            ]
        );
    }
}
