<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::where('email', '!=', 'destimuthiah@gmail.com')->delete();

        User::updateOrCreate(
            ['email' => 'destimuthiah@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Tiyan1717_'),
            ]
        );

        if (\App\Models\Operator::count() === 0) {
            $this->call(DemoSeeder::class);
        }
    }
}

