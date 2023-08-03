<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $amount = rand (2, 5);
            for ($i = 0; $i <= $amount; $i++) {
                $event = Event::factory()->make(['organization_id' => $user->id]);
                $event->save();
            }
        }
    }
}
