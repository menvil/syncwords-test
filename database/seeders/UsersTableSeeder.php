<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed test user 1
        $user = User::where('email', '=', 'admin@test.com')->first();
        if ($user === null) {
            $user = User::create([
                'name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => Carbon::now()
            ]);
        }

        // Seed test user 2
        $user = User::where('email', '=', 'user@test.com')->first();
        if ($user === null) {
            $user = User::create([
                'name' => 'User',
                'email' => 'user@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => Carbon::now()
            ]);
        }
    }
}
