<?php

namespace Database\Seeders;

use App\Models\Integration;
use App\Models\Review;
use App\Models\Todo;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Integration::factory()->count(5)->create();
        Review::factory()->count(200)->create();
    }
}
